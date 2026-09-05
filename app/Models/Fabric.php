<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fabric extends Model
{
    protected $fillable = [
        'name',
        'price_addition',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price_addition' => 'decimal:0',
            'is_active'      => 'boolean',
        ];
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
