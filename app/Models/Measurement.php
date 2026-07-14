<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Measurement extends Model
{
    protected $fillable = [
        'user_id',
        'neck',
        'chest',
        'waist',
        'hips',
        'shoulder_width',
        'shirt_length',
        'arm_length',
        'upper_arm',
        'wrist',
        'height',
        'pants_waist',
        'pants_hips',
        'thigh',
        'knee',
        'calf',
        'ankle',
        'inseam',
        'outseam',
        'rise',
        'photo_path',
        'front_photo_path',
        'side_photo_path',
        'back_photo_path',
        'ref_object',
        'ref_size',
        'ref_width_cm',
        'ref_height_cm',
        'measurement_method',
        'confidence_score',
        'quality_score',
        'raw_cv_json',
        'edited_fields_json',
        'is_edited',
    ];

    protected function casts(): array
    {
        return [
            'neck' => 'decimal:2',
            'chest' => 'decimal:2',
            'waist' => 'decimal:2',
            'hips' => 'decimal:2',
            'shoulder_width' => 'decimal:2',
            'shirt_length' => 'decimal:2',
            'arm_length' => 'decimal:2',
            'upper_arm' => 'decimal:2',
            'wrist' => 'decimal:2',
            'height' => 'decimal:2',
            'pants_waist' => 'decimal:2',
            'pants_hips' => 'decimal:2',
            'thigh' => 'decimal:2',
            'knee' => 'decimal:2',
            'calf' => 'decimal:2',
            'ankle' => 'decimal:2',
            'inseam' => 'decimal:2',
            'outseam' => 'decimal:2',
            'rise' => 'decimal:2',
            'ref_width_cm' => 'decimal:2',
            'ref_height_cm' => 'decimal:2',
            'confidence_score' => 'decimal:2',
            'quality_score' => 'decimal:2',
            'raw_cv_json' => 'array',
            'edited_fields_json' => 'array',
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
            'aruco_a4' => 'Marker ArUco A4',
            'checkerboard_a4' => 'Checkerboard A4',
            'custom' => 'Custom (' . $this->ref_size . ')',
            'manual' => 'Input Manual',
            default => $this->ref_object ?? 'Manual',
        };
    }

    public function getMeasurementMethodLabelAttribute(): string
    {
        return match ($this->measurement_method) {
            'multiview_cv' => 'CV Multi-view',
            'manual' => 'Input Manual',
            default => $this->measurement_method ?? 'Manual',
        };
    }
}
