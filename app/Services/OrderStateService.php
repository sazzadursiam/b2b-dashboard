<?php

namespace App\Services;

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
    private array $allowed = [
        'pending' => ['paid', 'canceled'],
        'paid' => ['processing', 'canceled'],
        'processing' => ['shipped', 'canceled'],
        'shipped' => ['delivered'],
        'delivered' => [],
        'canceled' => [],
    ];

    public function transition(
        Order $order,
        string $toState,
        ?User $user = null,
        array $metadata = []
    ): Order {
        return DB::transaction(function () use ($order, $toState, $user, $metadata) {
            $fromState = $order->status;

            if (! $this->canTransition($fromState, $toState)) {
                throw ValidationException::withMessages([
                    'status' => "Invalid transition from {$fromState} to {$toState}.",
                ]);
            }

            if ($toState === 'processing') {
                $reserved = $this->reserveInventory($order);

                if (! $reserved) {
                    RefundPaymentJob::dispatch($order->id);

                    throw ValidationException::withMessages([
                        'inventory' => 'Insufficient inventory. Refund initiated.',
                    ]);
                }
            }

            $order->update([
                'status' => $toState,
            ]);

            OrderAudit::create([
                'order_id' => $order->id,
                'business_id' => $order->business_id,
                'from_state' => $fromState,
                'to_state' => $toState,
                'user_id' => $user?->id,
                'metadata' => $metadata,
            ]);

            OrderStatusChanged::dispatch($order, $fromState, $toState);

            return $order->refresh();
        });
    }

    private function canTransition(string $from, string $to): bool
    {
        if ($to === 'canceled' && ! in_array($from, ['delivered', 'canceled'])) {
            return true;
        }

        return in_array($to, $this->allowed[$from] ?? []);
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
