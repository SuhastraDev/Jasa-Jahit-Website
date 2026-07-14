<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM(
                'pending','confirmed','waiting_item','item_received',
                'processing','done','shipped','completed','cancelled'
            ) DEFAULT 'pending'");

            DB::statement("ALTER TABLE orders ADD COLUMN design_file VARCHAR(255) NULL AFTER reference_image");
            DB::statement("ALTER TABLE orders ADD COLUMN design_notes TEXT NULL AFTER design_file");
            return;
        }

        Schema::table('orders', function (Blueprint $table): void {
            $table->string('design_file')->nullable();
            $table->text('design_notes')->nullable();
        });
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM(
                'pending','confirmed','processing','done','shipped','completed','cancelled'
            ) DEFAULT 'pending'");
            DB::statement("ALTER TABLE orders DROP COLUMN design_file");
            DB::statement("ALTER TABLE orders DROP COLUMN design_notes");
            return;
        }

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn(['design_file', 'design_notes']);
        });
    }
};
