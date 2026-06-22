<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TenantScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_listing_orders_returns_only_the_current_business(): void
    {
        [$businessA, $userA] = $this->makeBusinessWithUser();
        [$businessB] = $this->makeBusinessWithUser();

        Order::factory()->count(2)->create(['business_id' => $businessA->id]);
        Order::factory()->count(3)->create(['business_id' => $businessB->id]);

        Sanctum::actingAs($userA);

        $response = $this->getJson('/api/v1/orders')->assertOk();

        $this->assertCount(2, $response->json('data'));
        foreach ($response->json('data') as $order) {
            $this->assertSame($businessA->id, $order['business_id']);
        }
    }

    public function test_cannot_transition_another_businesss_order(): void
    {
        [$businessA, $userA] = $this->makeBusinessWithUser();
        [$businessB] = $this->makeBusinessWithUser();

        $foreignOrder = Order::factory()->create(['business_id' => $businessB->id, 'status' => 'pending']);

        Sanctum::actingAs($userA);

        $this->patchJson("/api/v1/orders/{$foreignOrder->id}/status", ['status' => 'paid'])
            ->assertNotFound();
    }
}
