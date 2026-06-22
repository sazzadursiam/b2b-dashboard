<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'owner_id',
        'timezone',
        'subscription_tier',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
