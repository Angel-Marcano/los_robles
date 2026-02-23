<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (Schema::hasTable('invoice_items') && !Schema::hasColumn('invoice_items','quantity')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                $table->unsignedInteger('quantity')->default(1)->after('base_amount_usd');
            });
        }
    }
    public function down(): void {
        if (Schema::hasTable('invoice_items') && Schema::hasColumn('invoice_items','quantity')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                $table->dropColumn('quantity');
            });
        }
    }
};
