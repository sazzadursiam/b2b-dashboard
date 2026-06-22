<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = [
        'business_id',
        'order_id',
        'product_id',
        'quantity',
        'total_amount',
    ];

    protected static function booted(): void
    {
        static::created(function (Sale $sale) {
            RefreshAnalyticsCache::dispatch($sale->business_id);
        });

        static::updated(function (Sale $sale) {
            RefreshAnalyticsCache::dispatch($sale->business_id);
        });
    }
}
