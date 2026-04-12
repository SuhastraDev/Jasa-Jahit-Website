<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM(
            'pending','confirmed','waiting_item','item_received',
            'processing','done','revision','shipped','completed','cancelled'
        ) DEFAULT 'pending'");

        DB::statement("ALTER TABLE orders ADD COLUMN revision_count TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER design_notes");
        DB::statement("ALTER TABLE orders ADD COLUMN revision_note TEXT NULL AFTER revision_count");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM(
            'pending','confirmed','waiting_item','item_received',
            'processing','done','shipped','completed','cancelled'
        ) DEFAULT 'pending'");
        DB::statement("ALTER TABLE orders DROP COLUMN revision_count");
        DB::statement("ALTER TABLE orders DROP COLUMN revision_note");
    }
};
