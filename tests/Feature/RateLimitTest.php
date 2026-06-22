<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_is_limited_to_200_requests_per_minute_per_business(): void
    {
        [$business, $user] = $this->makeBusinessWithUser();
        Order::factory()->create(['business_id' => $business->id, 'user_id' => $user->id]);

        Sanctum::actingAs($user);

        // First 200 requests are allowed (served from cache after the first).
        for ($i = 0; $i < 200; $i++) {
            $this->getJson('/api/v1/analytics/dashboard')->assertOk();
        }

        // The 201st request within the same minute is throttled.
        $this->getJson('/api/v1/analytics/dashboard')->assertStatus(429);
    }
}
