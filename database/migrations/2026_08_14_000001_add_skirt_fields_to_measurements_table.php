<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('measurements', function (Blueprint $table): void {
            $table->decimal('skirt_length', 6, 2)->nullable()->after('rise');
            $table->decimal('hem_width', 6, 2)->nullable()->after('skirt_length');
        });
    }

    public function down(): void
    {
        Schema::table('measurements', function (Blueprint $table): void {
            $table->dropColumn(['skirt_length', 'hem_width']);
        });
    }
};
