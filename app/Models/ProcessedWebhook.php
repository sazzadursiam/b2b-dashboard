<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcessedWebhook extends Model
{
    protected $fillable = [
        'transaction_id',
        'event',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}
