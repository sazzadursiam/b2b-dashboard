<?php

namespace App\Models\Scopes;

use App\Support\CurrentBusiness;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class BusinessScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $business = app(CurrentBusiness::class);

        if (! $business->isSet()) {
            return;
        }


        $builder->where($model->getTable().'.business_id', $business->id());
    }
}
