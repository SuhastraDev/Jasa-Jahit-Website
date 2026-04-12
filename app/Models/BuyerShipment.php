<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BuyerShipment extends Model
{
    protected $fillable = [
        'order_id',
        'expedition',
        'tracking_number',
        'proof_image',
        'notes',
        'shipped_at',
    ];

    protected $casts = [
        'shipped_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
