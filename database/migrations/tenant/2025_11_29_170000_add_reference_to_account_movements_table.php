<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('account_movements') && !Schema::hasColumn('account_movements','reference')) {
            Schema::table('account_movements', function (Blueprint $table) {
                $table->string('reference',200)->nullable()->after('amount_ves');
            });
        }
        if (Schema::hasTable('account_movements') && !Schema::hasColumn('account_movements','user_id')) {
            Schema::table('account_movements', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable()->after('reference');
            });
        }
        if (Schema::hasTable('account_movements') && !Schema::hasColumn('account_movements','meta')) {
            Schema::table('account_movements', function (Blueprint $table) {
                $table->json('meta')->nullable()->after('user_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('account_movements')) {
            Schema::table('account_movements', function (Blueprint $table) {
                if (Schema::hasColumn('account_movements','meta')) { $table->dropColumn('meta'); }
                if (Schema::hasColumn('account_movements','user_id')) { $table->dropColumn('user_id'); }
                if (Schema::hasColumn('account_movements','reference')) { $table->dropColumn('reference'); }
            });
        }
    }
};