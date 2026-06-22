<?php

namespace App\Http\Controllers\Webhook;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentWebhookRequest;
use App\Models\Order;
use App\Models\ProcessedWebhook;
use App\Services\OrderStateService;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    public function handle(PaymentWebhookRequest $request, OrderStateService $stateService)
    {
        $data = $request->validated();

        // Idempotency: the same transaction is processed exactly once.
        if (ProcessedWebhook::where('transaction_id', $data['transaction_id'])->exists()) {
            return response()->json(['status' => 'duplicate_ignored']);
        }

        ProcessedWebhook::create([
            'transaction_id' => $data['transaction_id'],
            'event' => $data['event'],
            'payload' => $data,
        ]);

        // Public webhook: no authenticated business, so orders resolve globally.
        $order = Order::find($data['order_id']);

        if (! $order) {
            Log::warning('Webhook received for missing order', $data);

            return response()->json(['status' => 'order_not_found_ignored']);
        }

        if ($data['event'] === 'payment_succeeded') {

            if ($order->status === OrderStatus::Pending) {
                $stateService->transition(
                    order: $order,
                    toState: OrderStatus::Paid,
                    user: null,
                    metadata: [
                        'source' => 'webhook',
                        'transaction_id' => $data['transaction_id'],
                    ],
                );

                return response()->json(['status' => 'payment_processed']);
            }

            Log::info('Payment webhook ignored because order state does not apply', [
                'order_id' => $order->id,
                'current_status' => $order->status->value,
            ]);

            return response()->json(['status' => 'ignored_current_state']);
        }

        Log::info('Webhook event ignored', $data);

        return response()->json(['status' => 'event_ignored']);
    }
}
