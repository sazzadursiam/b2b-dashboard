<?php

namespace App\Console\Commands;

use App\Jobs\RefreshAnalyticsCache;
use App\Models\Business;
use Illuminate\Console\Command;

class WarmAnalyticsCache extends Command
{
    protected $signature = 'analytics:warm-cache {--business=}';

    protected $description = 'Warm analytics cache for all businesses or a specific business';

    public function handle(): int
    {
        $start = microtime(true);

        $businessId = $this->option('business');

        $query = Business::query();

        if ($businessId) {
            $query->whereKey($businessId);
        }

        $businesses = $query->get();

        $bar = $this->output->createProgressBar($businesses->count());
        $bar->start();

        foreach ($businesses as $business) {
            RefreshAnalyticsCache::dispatch($business->id);
            $bar->advance();
        }

        $bar->finish();

        $this->newLine();

        $this->info('Dispatched ' . $businesses->count() . ' cache refresh jobs.');
        $this->info('Completed in ' . round(microtime(true) - $start, 2) . ' seconds.');

        return self::SUCCESS;
    }
}
