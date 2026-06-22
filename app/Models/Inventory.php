<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $fillable = [
        'business_id',
        'product_id',
        'stock',
        'reserved',
    ];
}
