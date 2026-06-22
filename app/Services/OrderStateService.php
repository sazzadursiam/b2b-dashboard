<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Events\OrderStatusChanged;
use App\Jobs\RefundPaymentJob;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderAudit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderStateService
{
    public function transition(
        Order $order,
        OrderStatus $toState,
        ?User $user = null,
        array $metadata = []
    ): Order {
        return DB::transaction(function () use ($order, $toState, $user, $metadata) {
            /** @var OrderStatus $fromState */
            $fromState = $order->status;

            if (! $fromState->canTransitionTo($toState)) {
                throw ValidationException::withMessages([
                    'status' => "Invalid transition from {$fromState->value} to {$toState->value}.",
                ]);
            }

            if ($toState === OrderStatus::Processing && ! $this->reserveInventory($order)) {
                RefundPaymentJob::dispatch($order->id);

                throw ValidationException::withMessages([
                    'inventory' => 'Insufficient inventory. Refund initiated.',
                ]);
            }

            $order->update([
                'status' => $toState,
            ]);

            OrderAudit::create([
                'order_id' => $order->id,
                'business_id' => $order->business_id,
                'from_state' => $fromState->value,
                'to_state' => $toState->value,
                'user_id' => $user?->id,
                'metadata' => $metadata,
            ]);

            OrderStatusChanged::dispatch($order, $fromState, $toState);

            return $order->refresh();
        });
    }

    private function reserveInventory(Order $order): bool
    {
        $order->load('items');

        foreach ($order->items as $item) {
            $inventory = Inventory::where('business_id', $order->business_id)
                ->where('product_id', $item->product_id)
                ->lockForUpdate()
                ->first();

            if (! $inventory || ($inventory->stock - $inventory->reserved) < $item->quantity) {
                return false;
            }
        }

        foreach ($order->items as $item) {
            Inventory::where('business_id', $order->business_id)
                ->where('product_id', $item->product_id)
                ->increment('reserved', $item->quantity);
        }

        return true;
    }
}
