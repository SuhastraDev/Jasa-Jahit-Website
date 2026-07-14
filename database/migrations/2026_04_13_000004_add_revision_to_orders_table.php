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
                'processing','done','revision','shipped','completed','cancelled'
            ) DEFAULT 'pending'");

            DB::statement("ALTER TABLE orders ADD COLUMN revision_count TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER design_notes");
            DB::statement("ALTER TABLE orders ADD COLUMN revision_note TEXT NULL AFTER revision_count");
            return;
        }

        Schema::table('orders', function (Blueprint $table): void {
            $table->unsignedTinyInteger('revision_count')->default(0);
            $table->text('revision_note')->nullable();
        });
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM(
                'pending','confirmed','waiting_item','item_received',
                'processing','done','shipped','completed','cancelled'
            ) DEFAULT 'pending'");
            DB::statement("ALTER TABLE orders DROP COLUMN revision_count");
            DB::statement("ALTER TABLE orders DROP COLUMN revision_note");
            return;
        }

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn(['revision_count', 'revision_note']);
        });
    }
};
