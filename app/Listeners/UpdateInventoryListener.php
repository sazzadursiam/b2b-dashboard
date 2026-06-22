<?php

namespace App\Listeners;

use App\Enums\OrderStatus;
use App\Events\OrderStatusChanged;
use App\Jobs\RefundPaymentJob;
use App\Models\Inventory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateInventoryListener implements ShouldQueue
{
    public function handle(OrderStatusChanged $event): void
    {
        $order = $event->order;

        if (! in_array($event->toState, [OrderStatus::Shipped, OrderStatus::Canceled], true)) {
            return;
        }

        $order->loadMissing('items');

        DB::transaction(function () use ($order, $event) {
            foreach ($order->items as $item) {
                $inventory = Inventory::where('business_id', $order->business_id)
                    ->where('product_id', $item->product_id)
                    ->lockForUpdate()
                    ->first();

                if (! $inventory) {
                    continue;
                }

                if ($event->toState === OrderStatus::Shipped) {
                    // Fulfil: remove the reservation and the physical stock.
                    $inventory->decrement('reserved', min($inventory->reserved, $item->quantity));
                    $inventory->decrement('stock', min($inventory->stock, $item->quantity));
                } elseif ($event->toState === OrderStatus::Canceled) {
                    // Compensate: free the reservation back to available stock.
                    $inventory->decrement('reserved', min($inventory->reserved, $item->quantity));
                }
            }
        });

        Log::info('INVENTORY UPDATED', [
            'order_id' => $order->id,
            'business_id' => $order->business_id,
            'to_state' => $event->toState->value,
        ]);

        // Releasing a reservation on cancel means we may owe the customer money.
        $refundableFrom = [OrderStatus::Paid, OrderStatus::Processing, OrderStatus::Shipped];

        if ($event->toState === OrderStatus::Canceled && in_array($event->fromState, $refundableFrom, true)) {
            RefundPaymentJob::dispatch($order->id);
        }
    }
}
