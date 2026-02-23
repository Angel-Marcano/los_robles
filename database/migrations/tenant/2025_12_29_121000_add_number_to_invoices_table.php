<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('invoices', 'number')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->string('number', 100)->nullable()->after('id');
                $table->index('number');
            });
        }
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices','number')) {
                $table->dropIndex(['number']);
                $table->dropColumn('number');
            }
        });
    }
};
