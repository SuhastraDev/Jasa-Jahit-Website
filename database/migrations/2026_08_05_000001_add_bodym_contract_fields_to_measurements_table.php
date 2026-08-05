<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const BODYM_FIELDS = [
        'ankle_girth',
        'arm_length',
        'bicep_girth',
        'calf_girth',
        'chest_girth',
        'forearm_girth',
        'height',
        'hip_girth',
        'leg_length',
        'shoulder_breadth',
        'shoulder_to_crotch',
        'thigh_girth',
        'waist_girth',
        'wrist_girth',
    ];

    public function up(): void
    {
        Schema::table('measurements', function (Blueprint $table): void {
            foreach (self::BODYM_FIELDS as $field) {
                $table->decimal("bodym_{$field}", 6, 2)->nullable()->after('rise');
            }

            $table->json('bodym_data')->nullable()->after('edited_fields_json');
            $table->json('bodym_per_field_confidence')->nullable()->after('bodym_data');
            $table->json('bodym_prediction_intervals_cm')->nullable()->after('bodym_per_field_confidence');
            $table->json('bodym_diagnostics')->nullable()->after('bodym_prediction_intervals_cm');
            $table->string('bodym_contract_version', 40)->nullable()->after('measurement_method');
            $table->string('bodym_response_contract_version', 40)->nullable()->after('bodym_contract_version');
            $table->string('bodym_model_version', 80)->nullable()->after('bodym_response_contract_version');
            $table->string('bodym_status', 40)->nullable()->after('bodym_model_version');
        });
    }

    public function down(): void
    {
        Schema::table('measurements', function (Blueprint $table): void {
            $table->dropColumn(array_merge(
                array_map(fn (string $field): string => "bodym_{$field}", self::BODYM_FIELDS),
                [
                    'bodym_data',
                    'bodym_per_field_confidence',
                    'bodym_prediction_intervals_cm',
                    'bodym_diagnostics',
                    'bodym_contract_version',
                    'bodym_response_contract_version',
                    'bodym_model_version',
                    'bodym_status',
                ],
            ));
        });
    }
};
