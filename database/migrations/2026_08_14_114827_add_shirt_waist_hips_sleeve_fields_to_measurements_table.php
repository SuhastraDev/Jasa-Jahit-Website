<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('measurements', function (Blueprint $table): void {
            $table->decimal('shirt_waist', 6, 2)->nullable()->after('chest');
            $table->decimal('shirt_hips', 6, 2)->nullable()->after('shirt_waist');
            $table->decimal('sleeve_opening', 6, 2)->nullable()->after('upper_arm');
        });
    }

    public function down(): void
    {
        Schema::table('measurements', function (Blueprint $table): void {
            $table->dropColumn(['shirt_waist', 'shirt_hips', 'sleeve_opening']);
        });
    }
};
