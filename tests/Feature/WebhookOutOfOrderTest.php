<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\ProcessedWebhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class WebhookOutOfOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_out_of_order_event_is_handled_gracefully(): void
    {
        Log::spy();

        [$business, $user] = $this->makeBusinessWithUser();
        $order = Order::factory()->create(['business_id' => $business->id, 'status' => 'pending']);

        $this->postJson('/webhooks/payment', [
            'event' => 'payment_succeeded',
            'order_id' => $order->id,
            'transaction_id' => 'txn-success',
        ])->assertOk()->assertJson(['status' => 'payment_processed']);

        $late = $this->postJson('/webhooks/payment', [
            'event' => 'payment_intent.created',
            'order_id' => $order->id,
            'transaction_id' => 'txn-intent',
        ]);

        $late->assertOk()->assertJson(['status' => 'event_ignored']);
        $this->assertSame(OrderStatus::Paid, $order->fresh()->status);

        Log::shouldHaveReceived('info')->atLeast()->once();
    }

    public function test_succeeded_event_for_non_pending_order_is_ignored(): void
    {
        [$business, $user] = $this->makeBusinessWithUser();
        $order = Order::factory()->create(['business_id' => $business->id, 'status' => 'shipped']);

        $this->postJson('/webhooks/payment', [
            'event' => 'payment_succeeded',
            'order_id' => $order->id,
            'transaction_id' => 'txn-late',
        ])->assertOk()->assertJson(['status' => 'ignored_current_state']);

        $this->assertSame(OrderStatus::Shipped, $order->fresh()->status);
    }

    public function test_duplicate_webhook_has_no_side_effects(): void
    {
        [$business, $user] = $this->makeBusinessWithUser();
        $order = Order::factory()->create(['business_id' => $business->id, 'status' => 'pending']);

        $payload = [
            'event' => 'payment_succeeded',
            'order_id' => $order->id,
            'transaction_id' => 'txn-dupe',
        ];

        $this->postJson('/webhooks/payment', $payload)->assertOk();
        $this->postJson('/webhooks/payment', $payload)
            ->assertOk()
            ->assertJson(['status' => 'duplicate_ignored']);

        $this->assertSame(1, ProcessedWebhook::where('transaction_id', 'txn-dupe')->count());
        $this->assertSame(OrderStatus::Paid, $order->fresh()->status);
    }
}
