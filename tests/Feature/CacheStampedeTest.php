<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CacheStampedeTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_one_request_rebuilds_the_dashboard_cache(): void
    {
        [$business, $user] = $this->makeBusinessWithUser();

        // Seed some orders so the aggregation has data to crunch.
        Order::factory()->count(5)->create([
            'business_id' => $business->id,
            'user_id' => $user->id,
        ]);

        Sanctum::actingAs($user);

        $rebuildQueries = 0;
        DB::listen(function ($query) use (&$rebuildQueries) {
            if (str_contains($query->sql, 'total_revenue')) {
                $rebuildQueries++;
            }
        });

        $cachedFlags = [];

        // Simulate 10 requests
        for ($i = 0; $i < 10; $i++) {
            $response = $this->getJson('/api/v1/analytics/dashboard');
            $response->assertOk();
            $cachedFlags[] = $response->json('cached');
        }

        //  one DB rebuild.
        $this->assertSame(1, $rebuildQueries, 'The dashboard was recomputed more than once.');

        // First response was a fresh build, the rest were served from cache.
        $this->assertFalse($cachedFlags[0]);
        $this->assertSame(9, count(array_filter(array_slice($cachedFlags, 1))));
    }

    public function test_dashboard_response_shape(): void
    {
        [$business, $user] = $this->makeBusinessWithUser();
        Order::factory()->create(['business_id' => $business->id, 'user_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/analytics/dashboard')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'total_revenue',
                    'total_orders',
                    'average_order_value',
                    'top_products',
                    'hourly_order_velocity',
                ],
                'generated_at',
                'cached',
            ]);
    }
}
