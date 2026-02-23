<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (!Schema::connection('tenant')->hasColumn('ownerships','active')) {
            Schema::connection('tenant')->table('ownerships', function (Blueprint $table) {
                $table->boolean('active')->default(true);
            });
        }
    }
    public function down(): void {
        if (Schema::connection('tenant')->hasColumn('ownerships','active')) {
            Schema::connection('tenant')->table('ownerships', function (Blueprint $table) {
                $table->dropColumn('active');
            });
        }
    }
};
