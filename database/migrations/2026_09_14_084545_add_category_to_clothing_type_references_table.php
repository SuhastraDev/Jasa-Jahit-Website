<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clothing_type_references', function (Blueprint $table) {
            $table->enum('category', ['baju', 'celana', 'rok'])->default('baju')->after('gender');
        });
    }

    public function down(): void
    {
        Schema::table('clothing_type_references', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
