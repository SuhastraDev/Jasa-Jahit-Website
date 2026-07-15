<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM(
                'pending','confirmed','waiting_item','item_received',
                'processing','done','shipped','completed','cancelled'
            ) DEFAULT 'pending'");
        }

        Schema::table('orders', function (Blueprint $table): void {
            $table->string('design_file')->nullable()->after('reference_image');
            $table->text('design_notes')->nullable()->after('design_file');
        });
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM(
                'pending','confirmed','processing','done','shipped','completed','cancelled'
            ) DEFAULT 'pending'");
        }

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn(['design_file', 'design_notes']);
        });
    }
};
