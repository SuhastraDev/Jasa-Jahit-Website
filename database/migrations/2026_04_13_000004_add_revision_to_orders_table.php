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
                'processing','done','revision','shipped','completed','cancelled'
            ) DEFAULT 'pending'");
        }

        Schema::table('orders', function (Blueprint $table): void {
            $table->unsignedTinyInteger('revision_count')->default(0)->after('design_notes');
            $table->text('revision_note')->nullable()->after('revision_count');
        });
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM(
                'pending','confirmed','waiting_item','item_received',
                'processing','done','shipped','completed','cancelled'
            ) DEFAULT 'pending'");
        }

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn(['revision_count', 'revision_note']);
        });
    }
};
