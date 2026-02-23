<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::connection('tenant')->table('invoice_items', function (Blueprint $table) {
            if (!Schema::connection('tenant')->hasColumn('invoice_items','expense_item_id')) {
                $table->unsignedBigInteger('expense_item_id')->nullable()->after('apartment_id');
            }
        });
    }
    public function down(): void {
        Schema::connection('tenant')->table('invoice_items', function (Blueprint $table) {
            if (Schema::connection('tenant')->hasColumn('invoice_items','expense_item_id')) {
                $table->dropColumn('expense_item_id');
            }
        });
    }
};
