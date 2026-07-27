<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega el campo reserve_percent a la tabla master `condominiums`
 * para soportar el fondo de reserva general del condominio.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('condominiums') && !Schema::hasColumn('condominiums', 'reserve_percent')) {
            Schema::table('condominiums', function (Blueprint $table) {
                $table->decimal('reserve_percent', 5, 2)->default(0)->after('active');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('condominiums') && Schema::hasColumn('condominiums', 'reserve_percent')) {
            Schema::table('condominiums', function (Blueprint $table) {
                $table->dropColumn('reserve_percent');
            });
        }
    }
};