<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('expense_items')) {
            Schema::table('expense_items', function (Blueprint $table) {
                if (Schema::hasColumn('expense_items','type')) { $table->dropColumn('type'); }
                if (Schema::hasColumn('expense_items','amount_usd')) { $table->dropColumn('amount_usd'); }
            });
        }
    }
    public function down(): void
    {
        if (Schema::hasTable('expense_items')) {
            Schema::table('expense_items', function (Blueprint $table) {
                if (!Schema::hasColumn('expense_items','type')) { $table->string('type')->nullable(); }
                if (!Schema::hasColumn('expense_items','amount_usd')) { $table->decimal('amount_usd',10,2)->default(0); }
            });
        }
    }
};