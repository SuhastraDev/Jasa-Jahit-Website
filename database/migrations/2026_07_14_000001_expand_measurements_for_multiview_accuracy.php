<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('measurements', function (Blueprint $table): void {
            $table->string('front_photo_path')->nullable()->after('photo_path');
            $table->string('side_photo_path')->nullable()->after('front_photo_path');
            $table->string('back_photo_path')->nullable()->after('side_photo_path');
            $table->decimal('ref_width_cm', 6, 2)->nullable()->after('ref_size');
            $table->decimal('ref_height_cm', 6, 2)->nullable()->after('ref_width_cm');
            $table->string('measurement_method', 50)->default('manual')->after('ref_height_cm');
            $table->decimal('confidence_score', 5, 2)->nullable()->after('measurement_method');
            $table->decimal('quality_score', 5, 2)->nullable()->after('confidence_score');
            $table->json('raw_cv_json')->nullable()->after('quality_score');
            $table->json('edited_fields_json')->nullable()->after('raw_cv_json');

            $table->decimal('neck', 5, 2)->nullable()->after('user_id');
            $table->decimal('shirt_length', 5, 2)->nullable()->after('shoulder_width');
            $table->decimal('upper_arm', 5, 2)->nullable()->after('arm_length');
            $table->decimal('wrist', 5, 2)->nullable()->after('upper_arm');
            $table->decimal('pants_waist', 5, 2)->nullable()->after('height');
            $table->decimal('pants_hips', 5, 2)->nullable()->after('pants_waist');
            $table->decimal('thigh', 5, 2)->nullable()->after('pants_hips');
            $table->decimal('knee', 5, 2)->nullable()->after('thigh');
            $table->decimal('calf', 5, 2)->nullable()->after('knee');
            $table->decimal('ankle', 5, 2)->nullable()->after('calf');
            $table->decimal('inseam', 5, 2)->nullable()->after('ankle');
            $table->decimal('outseam', 5, 2)->nullable()->after('inseam');
            $table->decimal('rise', 5, 2)->nullable()->after('outseam');
        });
    }

    public function down(): void
    {
        Schema::table('measurements', function (Blueprint $table): void {
            $table->dropColumn([
                'front_photo_path',
                'side_photo_path',
                'back_photo_path',
                'ref_width_cm',
                'ref_height_cm',
                'measurement_method',
                'confidence_score',
                'quality_score',
                'raw_cv_json',
                'edited_fields_json',
                'neck',
                'shirt_length',
                'upper_arm',
                'wrist',
                'pants_waist',
                'pants_hips',
                'thigh',
                'knee',
                'calf',
                'ankle',
                'inseam',
                'outseam',
                'rise',
            ]);
        });
    }
};
