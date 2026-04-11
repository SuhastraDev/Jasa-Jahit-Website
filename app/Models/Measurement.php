<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Measurement extends Model
{
    protected $fillable = [
        'user_id',
        'chest',
        'waist',
        'hips',
        'shoulder_width',
        'arm_length',
        'height',
        'photo_path',
        'ref_object',
        'ref_size',
        'is_edited',
    ];

    protected function casts(): array
    {
        return [
            'chest' => 'decimal:2',
            'waist' => 'decimal:2',
            'hips' => 'decimal:2',
            'shoulder_width' => 'decimal:2',
            'arm_length' => 'decimal:2',
            'height' => 'decimal:2',
            'is_edited' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get ref object label
     */
    public function getRefObjectLabelAttribute(): string
    {
        return match ($this->ref_object) {
            'a4' => 'Kertas A4',
            'atm' => 'Kartu ATM/KTP',
            'custom' => 'Custom (' . $this->ref_size . ')',
            'manual' => 'Input Manual',
            default => $this->ref_object ?? 'Manual',
        };
    }
}
