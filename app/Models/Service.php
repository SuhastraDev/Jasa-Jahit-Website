<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'base_price',
        'estimated_days',
        'is_active',
    ];

    public function catalogs()
    {
        return $this->hasMany(Catalog::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
