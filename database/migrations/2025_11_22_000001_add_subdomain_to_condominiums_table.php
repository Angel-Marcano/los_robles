<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('condominiums', function (Blueprint $table) {
            if (!Schema::hasColumn('condominiums', 'subdomain')) {
                $table->string('subdomain')->nullable()->unique()->after('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('condominiums', function (Blueprint $table) {
            if (Schema::hasColumn('condominiums', 'subdomain')) {
                $table->dropUnique(['subdomain']);
                $table->dropColumn('subdomain');
            }
        });
    }
};
