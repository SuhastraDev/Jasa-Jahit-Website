<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClothingTypeReference extends Model
{
    protected $fillable = [
        'name',
        'gender',
        'reference_image',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
