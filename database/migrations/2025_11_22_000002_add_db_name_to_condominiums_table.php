<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('condominiums', function (Blueprint $table) {
            if (!Schema::hasColumn('condominiums', 'db_name')) {
                $table->string('db_name')->nullable()->after('subdomain');
            }
        });
    }

    public function down(): void
    {
        Schema::table('condominiums', function (Blueprint $table) {
            if (Schema::hasColumn('condominiums', 'db_name')) {
                $table->dropColumn('db_name');
            }
        });
    }
};
