<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IdempotencyKey extends Model
{
    protected $fillable = [
        'business_id',
        'key',
        'endpoint',
        'response_body',
        'status_code',
        'expires_at',
    ];

    protected $casts = [
        'response_body' => 'array',
        'expires_at' => 'datetime',
    ];
}
