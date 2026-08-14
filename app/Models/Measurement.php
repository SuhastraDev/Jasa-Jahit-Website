<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Measurement extends Model
{
    protected $fillable = [
        'user_id',
        'neck',
        'chest',
        'shirt_waist',
        'shirt_hips',
        'waist',
        'hips',
        'shoulder_width',
        'shirt_length',
        'arm_length',
        'upper_arm',
        'sleeve_opening',
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
        'skirt_length',
        'hem_width',
        'photo_path',
        'front_photo_path',
        'side_photo_path',
        'back_photo_path',
        'ref_object',
        'ref_size',
        'ref_width_cm',
        'ref_height_cm',
        'reference_mode',
        'measurement_method',
        'bodym_contract_version',
        'bodym_response_contract_version',
        'bodym_model_version',
        'bodym_status',
        'confidence_score',
        'quality_score',
        'raw_cv_json',
        'garment_overlays_json',
        'bodym_data',
        'bodym_per_field_confidence',
        'bodym_prediction_intervals_cm',
        'bodym_diagnostics',
        'edited_fields_json',
        'is_edited',
        'bodym_ankle_girth',
        'bodym_arm_length',
        'bodym_bicep_girth',
        'bodym_calf_girth',
        'bodym_chest_girth',
        'bodym_forearm_girth',
        'bodym_height',
        'bodym_hip_girth',
        'bodym_leg_length',
        'bodym_shoulder_breadth',
        'bodym_shoulder_to_crotch',
        'bodym_thigh_girth',
        'bodym_waist_girth',
        'bodym_wrist_girth',
    ];

    protected function casts(): array
    {
        return [
            'neck' => 'decimal:2',
            'chest' => 'decimal:2',
            'shirt_waist' => 'decimal:2',
            'shirt_hips' => 'decimal:2',
            'waist' => 'decimal:2',
            'hips' => 'decimal:2',
            'shoulder_width' => 'decimal:2',
            'shirt_length' => 'decimal:2',
            'arm_length' => 'decimal:2',
            'upper_arm' => 'decimal:2',
            'sleeve_opening' => 'decimal:2',
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
            'skirt_length' => 'decimal:2',
            'hem_width' => 'decimal:2',
            'ref_width_cm' => 'decimal:2',
            'ref_height_cm' => 'decimal:2',
            'confidence_score' => 'decimal:2',
            'quality_score' => 'decimal:2',
            'raw_cv_json' => 'array',
            'garment_overlays_json' => 'array',
            'bodym_data' => 'array',
            'bodym_per_field_confidence' => 'array',
            'bodym_prediction_intervals_cm' => 'array',
            'bodym_diagnostics' => 'array',
            'edited_fields_json' => 'array',
            'is_edited' => 'boolean',
            'bodym_ankle_girth' => 'decimal:2',
            'bodym_arm_length' => 'decimal:2',
            'bodym_bicep_girth' => 'decimal:2',
            'bodym_calf_girth' => 'decimal:2',
            'bodym_chest_girth' => 'decimal:2',
            'bodym_forearm_girth' => 'decimal:2',
            'bodym_height' => 'decimal:2',
            'bodym_hip_girth' => 'decimal:2',
            'bodym_leg_length' => 'decimal:2',
            'bodym_shoulder_breadth' => 'decimal:2',
            'bodym_shoulder_to_crotch' => 'decimal:2',
            'bodym_thigh_girth' => 'decimal:2',
            'bodym_waist_girth' => 'decimal:2',
            'bodym_wrist_girth' => 'decimal:2',
        ];
    }

    /**
     * PostgreSQL does not accept PDO's integer binding for a boolean column.
     * Keep the public model value boolean while binding PostgreSQL literals.
     */
    public function setIsEditedAttribute(mixed $value): void
    {
        $normalized = filter_var($value, FILTER_VALIDATE_BOOLEAN);

        $this->attributes['is_edited'] = $this->getConnection()->getDriverName() === 'pgsql'
            ? ($normalized ? 'true' : 'false')
            : $normalized;
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
            'ktp', 'atm' => 'KTP',
            'aruco_a4' => 'Marker ArUco A4',
            'checkerboard_a4' => 'Checkerboard A4',
            'custom' => 'Custom (' . $this->ref_size . ')',
            'manual' => 'Input Manual',
            default => $this->ref_object ?? 'Manual',
        };
    }

    public function getReferenceModeLabelAttribute(): string
    {
        return match ($this->reference_mode) {
            'handheld' => 'Mode Praktis',
            'fixed' => 'Mode Akurat',
            default => 'Mode Akurat',
        };
    }

    public function getMeasurementMethodLabelAttribute(): string
    {
        return match ($this->measurement_method) {
            'bodym_ml' => 'BodyM ML',
            'multiview_cv' => 'CV Multi-view',
            'garment_flat_lay' => 'Baju/Celana Flat-Lay',
            'manual' => 'Input Manual',
            default => $this->measurement_method ?? 'Manual',
        };
    }
}
