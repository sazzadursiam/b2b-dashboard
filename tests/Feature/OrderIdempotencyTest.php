<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_repeated_request_with_same_idempotency_key_creates_one_order(): void
    {
        [$business, $user] = $this->makeBusinessWithUser();
        $product = Product::factory()->create(['business_id' => $business->id, 'price' => 25]);

        Sanctum::actingAs($user);

        $payload = ['items' => [['product_id' => $product->id, 'quantity' => 2]]];
        $headers = ['Idempotency-Key' => 'idem-key-123'];

        $first = $this->postJson('/api/v1/orders', $payload, $headers);
        $second = $this->postJson('/api/v1/orders', $payload, $headers);

        $first->assertCreated();
        $second->assertCreated();

        // Exactly one order persisted.
        $this->assertSame(1, Order::where('business_id', $business->id)->count());

        // The retry returns the original order, not a freshly created one.
        $this->assertSame($first->json('order.id'), $second->json('order.id'));

        // Same response payload returned for the retry (order-insensitive).
        $this->assertEquals($first->json(), $second->json());
    }

    public function test_business_id_is_assigned_from_authenticated_user_not_input(): void
    {
        [$business, $user] = $this->makeBusinessWithUser();
        $product = Product::factory()->create(['business_id' => $business->id, 'price' => 10]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/orders', [
            'business_id' => 999999, // attempt to spoof - must be ignored
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertCreated();

        $this->assertSame($business->id, Order::first()->business_id);
    }
}
