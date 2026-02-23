<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // Evitar error si ya existen las columnas en algunos tenants
        if (!Schema::connection('tenant')->hasColumn('users','document_type')) {
            Schema::connection('tenant')->table('users', function (Blueprint $table) {
                $table->string('document_type', 20)->nullable(); // cedula | pasaporte
            });
        }
        if (!Schema::connection('tenant')->hasColumn('users','document_number')) {
            Schema::connection('tenant')->table('users', function (Blueprint $table) {
                $table->string('document_number', 50)->nullable();
            });
        }
    }
    public function down(): void {
        Schema::connection('tenant')->table('users', function (Blueprint $table) {
            $table->dropColumn(['document_type','document_number']);
        });
    }
};
