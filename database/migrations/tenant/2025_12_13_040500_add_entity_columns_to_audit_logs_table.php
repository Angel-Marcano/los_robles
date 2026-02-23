<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::connection('tenant')->table('audit_logs', function (Blueprint $table) {
            if (!Schema::connection('tenant')->hasColumn('audit_logs','entity_type')) {
                $table->string('entity_type', 80)->nullable()->after('action');
            }
            if (!Schema::connection('tenant')->hasColumn('audit_logs','entity_id')) {
                $table->unsignedBigInteger('entity_id')->nullable()->after('entity_type');
            }
            if (!Schema::connection('tenant')->hasColumn('audit_logs','changes')) {
                $table->json('changes')->nullable()->after('entity_id');
            }
            if (!Schema::connection('tenant')->hasColumn('audit_logs','ip')) {
                $table->string('ip', 45)->nullable()->after('changes');
            }
        });
    }
    public function down(): void {
        Schema::connection('tenant')->table('audit_logs', function (Blueprint $table) {
            if (Schema::connection('tenant')->hasColumn('audit_logs','ip')) {
                $table->dropColumn('ip');
            }
            if (Schema::connection('tenant')->hasColumn('audit_logs','changes')) {
                $table->dropColumn('changes');
            }
            if (Schema::connection('tenant')->hasColumn('audit_logs','entity_id')) {
                $table->dropColumn('entity_id');
            }
            if (Schema::connection('tenant')->hasColumn('audit_logs','entity_type')) {
                $table->dropColumn('entity_type');
            }
        });
    }
};
