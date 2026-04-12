<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Seed default values
        DB::table('settings')->insert([
            ['key' => 'dana_number', 'value' => env('DANA_NUMBER', ''), 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'dana_name',   'value' => env('DANA_NAME', ''),   'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
