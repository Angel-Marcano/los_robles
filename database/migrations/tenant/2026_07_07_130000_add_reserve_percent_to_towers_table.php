<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (Schema::hasTable('towers') && !Schema::hasColumn('towers','reserve_percent')) {
            Schema::table('towers', function (Blueprint $table) {
                $table->decimal('reserve_percent', 5, 2)->default(0)->after('active');
            });
        }
    }
    public function down(): void {
        if (Schema::hasTable('towers') && Schema::hasColumn('towers','reserve_percent')) {
            Schema::table('towers', function (Blueprint $table) {
                $table->dropColumn('reserve_percent');
            });
        }
    }
};
