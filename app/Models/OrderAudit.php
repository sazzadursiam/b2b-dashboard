<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderAudit extends Model
{
    protected $fillable = [
        'order_id',
        'business_id',
        'from_state',
        'to_state',
        'user_id',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];
}
