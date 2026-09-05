<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('fabric_id')->nullable()->after('material')->constrained('fabrics')->nullOnDelete();
            $table->enum('gender', ['pria', 'wanita'])->nullable()->after('clothing_type');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fabric_id');
            $table->dropColumn('gender');
        });
    }
};
