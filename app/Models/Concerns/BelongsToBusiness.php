<?php

namespace App\Models\Concerns;

use App\Models\Business;
use App\Models\Scopes\BusinessScope;
use App\Support\CurrentBusiness;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Applies automatic multi-tenant isolation to a model:
 *
 *  - reads are filtered to the current business via a global scope;
 *  - writes have business_id auto-assigned from the current business
 *    (never from user input) when it is not explicitly provided.
 */
trait BelongsToBusiness
{
    protected static function bootBelongsToBusiness(): void
    {
        static::addGlobalScope(new BusinessScope);

        static::creating(function ($model) {
            if (! empty($model->business_id)) {
                return;
            }

            $business = app(CurrentBusiness::class);

            if ($business->isSet()) {
                $model->business_id = $business->id();
            }
        });
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
