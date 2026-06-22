<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class OrderAudit extends Model
{
    use BelongsToBusiness;

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
