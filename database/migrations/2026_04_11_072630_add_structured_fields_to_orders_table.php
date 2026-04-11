<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Detail pakaian
            $table->string('clothing_type')->nullable()->after('description');
            $table->string('color')->nullable()->after('clothing_type');
            $table->string('material')->nullable()->after('color');

            // Alamat terstruktur
            $table->string('province')->nullable()->after('address');
            $table->string('city')->nullable()->after('province');
            $table->string('district')->nullable()->after('city');
            $table->string('village')->nullable()->after('district');
            $table->string('postal_code', 10)->nullable()->after('village');
            $table->string('rt', 5)->nullable()->after('postal_code');
            $table->string('rw', 5)->nullable()->after('rt');
            $table->text('detail_address')->nullable()->after('rw');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'clothing_type', 'color', 'material',
                'province', 'city', 'district', 'village',
                'postal_code', 'rt', 'rw', 'detail_address',
            ]);
        });
    }
};
