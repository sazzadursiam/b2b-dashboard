<?php

namespace Tests\Feature;

use App\Jobs\RefreshAnalyticsCache;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SaleJobDispatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_sale_dispatches_refresh_job_for_its_business(): void
    {
        Queue::fake();

        [$business, $user] = $this->makeBusinessWithUser();
        $product = Product::factory()->create(['business_id' => $business->id]);

        Sale::factory()->create([
            'business_id' => $business->id,
            'product_id' => $product->id,
        ]);

        Queue::assertPushed(
            RefreshAnalyticsCache::class,
            fn (RefreshAnalyticsCache $job) => $job->businessId === $business->id
        );
    }

    public function test_refresh_job_is_unique_per_business(): void
    {
        [$business] = $this->makeBusinessWithUser();

        $job = new RefreshAnalyticsCache($business->id);

        $this->assertSame((string) $business->id, $job->uniqueId());
    }
}
