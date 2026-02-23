<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Helper para saber si existe FK por nombre (sin Doctrine)
        $fkExists = function(string $table, string $fkName): bool {
            $rows = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = database() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?", [$table, $fkName]);
            return !empty($rows);
        };

        if (Schema::hasTable('invoices') && !$fkExists('invoices','invoices_created_by_foreign')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->foreign('created_by','invoices_created_by_foreign')->references('id')->on('users')->nullOnDelete();
            });
        }
        if (Schema::hasTable('ownerships') && !$fkExists('ownerships','ownerships_user_id_foreign')) {
            Schema::table('ownerships', function (Blueprint $table) {
                $table->foreign('user_id','ownerships_user_id_foreign')->references('id')->on('users')->onDelete('cascade');
            });
        }
        if (Schema::hasTable('payment_reports') && !$fkExists('payment_reports','payment_reports_user_id_foreign')) {
            Schema::table('payment_reports', function (Blueprint $table) {
                $table->foreign('user_id','payment_reports_user_id_foreign')->references('id')->on('users')->onDelete('cascade');
            });
        }
        if (Schema::hasTable('audit_logs') && !$fkExists('audit_logs','audit_logs_user_id_foreign')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->foreign('user_id','audit_logs_user_id_foreign')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        // Eliminar FKs si existen
        $dropFk = function(string $table, string $fk) {
            if (Schema::hasTable($table)) {
                Schema::table($table,function(Blueprint $t) use ($fk){
                    try { $t->dropForeign($fk); } catch(\Throwable $e) {}
                });
            }
        };
        $dropFk('invoices','invoices_created_by_foreign');
        $dropFk('ownerships','ownerships_user_id_foreign');
        $dropFk('payment_reports','payment_reports_user_id_foreign');
        $dropFk('audit_logs','audit_logs_user_id_foreign');
    }
};
