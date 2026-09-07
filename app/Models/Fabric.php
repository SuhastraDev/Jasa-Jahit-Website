<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fabric extends Model
{
    protected $fillable = [
        'name',
        'price_addition',
        'stock_meters',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price_addition' => 'decimal:0',
            'stock_meters'   => 'decimal:2',
            'is_active'      => 'boolean',
        ];
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
