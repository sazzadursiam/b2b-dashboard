<?php

namespace App\Jobs;

use App\Services\DashboardAnalyticsService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

class RefreshAnalyticsCache implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public int $uniqueFor = 60;

    public function __construct(
        public int $businessId
    ) {}

    public function uniqueId(): string
    {
        return (string) $this->businessId;
    }

    public function handle(DashboardAnalyticsService $service): void
    {
        $lock = Cache::lock("analytics_business_{$this->businessId}", 10);

        if (! $lock->get()) {
            return;
        }

        try {
            $service->refreshCache($this->businessId);
        } finally {
            $lock->release();
        }
    }
}
