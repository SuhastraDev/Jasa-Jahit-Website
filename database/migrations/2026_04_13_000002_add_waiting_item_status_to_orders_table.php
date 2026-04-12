<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM(
            'pending','confirmed','waiting_item','item_received',
            'processing','done','shipped','completed','cancelled'
        ) DEFAULT 'pending'");

        DB::statement("ALTER TABLE orders ADD COLUMN design_file VARCHAR(255) NULL AFTER reference_image");
        DB::statement("ALTER TABLE orders ADD COLUMN design_notes TEXT NULL AFTER design_file");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM(
            'pending','confirmed','processing','done','shipped','completed','cancelled'
        ) DEFAULT 'pending'");
        DB::statement("ALTER TABLE orders DROP COLUMN design_file");
        DB::statement("ALTER TABLE orders DROP COLUMN design_notes");
    }
};
