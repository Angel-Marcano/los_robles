<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (Schema::hasTable('invoice_items') && !Schema::hasColumn('invoice_items','is_reserve')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                $table->boolean('is_reserve')->default(false)->after('distributed');
            });
        }
    }
    public function down(): void {
        if (Schema::hasTable('invoice_items') && Schema::hasColumn('invoice_items','is_reserve')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                $table->dropColumn('is_reserve');
            });
        }
    }
};
