<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->enum('type', ['custom', 'design', 'permak'])->default('custom')->after('name');
        });

        // Sesuaikan type berdasarkan nama layanan yang sudah ada
        DB::table('services')->where('name', 'like', '%permak%')->update(['type' => 'permak']);
        DB::table('services')->where('name', 'like', '%desain%')->orWhere('name', 'like', '%design%')->update(['type' => 'design']);
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
