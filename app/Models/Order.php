<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'business_id',
        'user_id',
        'status',
        'total_amount',
        'idempotency_key',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
