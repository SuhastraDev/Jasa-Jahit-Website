<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Catalog extends Model
{
    protected $fillable = [
        'service_id',
        'name',
        'description',
        'image_path',
        'is_active',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
