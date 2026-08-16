<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega campos de consentimiento legal a los usuarios (tenant):
 *  - accepted_privacy_at: fecha de aceptación de la política de privacidad.
 *  - accepted_terms_at: fecha de aceptación de términos y condiciones.
 *  - legal_version: versión de los documentos aceptados (ej: "1.0").
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'accepted_privacy_at')) {
                $table->timestamp('accepted_privacy_at')->nullable()->after('active');
            }
            if (!Schema::hasColumn('users', 'accepted_terms_at')) {
                $table->timestamp('accepted_terms_at')->nullable()->after('accepted_privacy_at');
            }
            if (!Schema::hasColumn('users', 'legal_version')) {
                $table->string('legal_version', 10)->nullable()->after('accepted_terms_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['accepted_privacy_at', 'accepted_terms_at', 'legal_version'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};