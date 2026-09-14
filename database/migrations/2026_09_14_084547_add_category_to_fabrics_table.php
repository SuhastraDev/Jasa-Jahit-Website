<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fabrics', function (Blueprint $table) {
            $table->enum('category', ['baju', 'celana', 'rok'])->default('baju')->after('name');
        });

        Schema::table('fabrics', function (Blueprint $table) {
            $table->dropUnique('fabrics_name_unique');
            $table->unique(['name', 'category']);
        });
    }

    public function down(): void
    {
        Schema::table('fabrics', function (Blueprint $table) {
            $table->dropUnique(['name', 'category']);
            $table->unique('name');
            $table->dropColumn('category');
        });
    }
};
