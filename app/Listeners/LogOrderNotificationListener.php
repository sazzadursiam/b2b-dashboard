<?php

namespace App\Listeners;

use App\Events\OrderStatusChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class LogOrderNotificationListener implements ShouldQueue
{
    public function handle(OrderStatusChanged $event): void
    {
        Log::info('ORDER STATUS CHANGED', [
            'order_id' => $event->order->id,
            'business_id' => $event->order->business_id,
            'from' => $event->fromState->value,
            'to' => $event->toState->value,
        ]);
    }
}
