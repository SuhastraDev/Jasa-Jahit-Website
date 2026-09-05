<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clothing_type_references', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->enum('gender', ['pria', 'wanita', 'unisex'])->default('unisex');
            $table->string('reference_image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clothing_type_references');
    }
};
