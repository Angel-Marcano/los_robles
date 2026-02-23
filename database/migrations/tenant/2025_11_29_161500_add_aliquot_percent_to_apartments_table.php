<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('apartments') && !Schema::hasColumn('apartments','aliquot_percent')) {
            Schema::table('apartments', function (Blueprint $table) {
                $table->decimal('aliquot_percent',8,4)->default(0.0000)->after('code');
            });
        }
    }
    public function down(): void
    {
        if (Schema::hasTable('apartments') && Schema::hasColumn('apartments','aliquot_percent')) {
            Schema::table('apartments', function (Blueprint $table) {
                $table->dropColumn('aliquot_percent');
            });
        }
    }
};