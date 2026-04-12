<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'name',
        'type',
        'slug',
        'description',
        'base_price',
        'estimated_days',
        'is_active',
    ];

    // Label tipe layanan
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'custom'  => 'Custom (Jahit Baru)',
            'design'  => 'Desain Digital',
            'permak'  => 'Permak / Perbaikan',
            default   => $this->type,
        };
    }

    public function getTypeBadgeColorAttribute(): string
    {
        return match ($this->type) {
            'custom'  => 'blue',
            'design'  => 'purple',
            'permak'  => 'orange',
            default   => 'gray',
        };
    }

    public function catalogs()
    {
        return $this->hasMany(Catalog::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
