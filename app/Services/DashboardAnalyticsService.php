<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardAnalyticsService
{
    public function getDashboard(int $businessId): array
    {
        $cacheKey = $this->cacheKey($businessId);
        $tags = $this->tags($businessId);

        $cached = Cache::tags($tags)->get($cacheKey);

        if ($cached) {
            return [
                'data' => $cached['data'],
                'generated_at' => $cached['generated_at'],
                'cached' => true,
            ];
        }

        $lock = Cache::lock($this->lockKey($businessId), 10);

        return $lock->block(5, function () use ($businessId, $cacheKey, $tags) {
            $cached = Cache::tags($tags)->get($cacheKey);

            if ($cached) {
                return [
                    'data' => $cached['data'],
                    'generated_at' => $cached['generated_at'],
                    'cached' => true,
                ];
            }

            $data = $this->recompute($businessId);

            $payload = [
                'data' => $data,
                'generated_at' => now()->toISOString(),
            ];

            Cache::tags($tags)->put($cacheKey, $payload, now()->addHour());

            return [
                'data' => $payload['data'],
                'generated_at' => $payload['generated_at'],
                'cached' => false,
            ];
        });
    }

    public function recompute(int $businessId): array
    {
        $from30Days = now()->subDays(30);
        $from24Hours = now()->subHours(24);

        $summary = DB::table('orders')
            ->where('business_id', $businessId)
            ->where('created_at', '>=', $from30Days)
            ->selectRaw('
                COALESCE(SUM(total_amount), 0) as total_revenue,
                COUNT(*) as total_orders,
                COALESCE(AVG(total_amount), 0) as average_order_value
            ')
            ->first();

        $topProducts = DB::table('order_items')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->where('order_items.business_id', $businessId)
            ->where('order_items.created_at', '>=', $from30Days)
            ->groupBy('products.id', 'products.name')
            ->orderByDesc(DB::raw('SUM(order_items.quantity)'))
            ->limit(10)
            ->get([
                'products.id',
                'products.name',
                DB::raw('SUM(order_items.quantity) as total_quantity'),
            ]);

        $hourExpression = $this->hourBucketExpression();

        $hourlyVelocity = DB::table('orders')
            ->where('business_id', $businessId)
            ->where('created_at', '>=', $from24Hours)
            ->selectRaw("{$hourExpression} as hour, COUNT(*) as total_orders")
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        return [
            'total_revenue' => (float) $summary->total_revenue,
            'total_orders' => (int) $summary->total_orders,
            'average_order_value' => (float) $summary->average_order_value,
            'top_products' => $topProducts,
            'hourly_order_velocity' => $hourlyVelocity,
        ];
    }

    public function refreshCache(int $businessId): array
    {
        $data = $this->recompute($businessId);

        $payload = [
            'data' => $data,
            'generated_at' => now()->toISOString(),
        ];

        Cache::tags($this->tags($businessId))
            ->put($this->cacheKey($businessId), $payload, now()->addHour());

        return $payload;
    }

    private function hourBucketExpression(): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m-%d %H:00:00', created_at)",
            'pgsql' => "to_char(date_trunc('hour', created_at), 'YYYY-MM-DD HH24:00:00')",
            default => "DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00')",
        };
    }

    private function cacheKey(int $businessId): string
    {
        return "analytics_dashboard_{$businessId}";
    }

    private function lockKey(int $businessId): string
    {
        return "analytics_business_{$businessId}";
    }

    private function tags(int $businessId): array
    {
        return ["business_{$businessId}"];
    }
}
