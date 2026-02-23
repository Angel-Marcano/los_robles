<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('currency_rates')) {
            // Añadir columnas faltantes sólo si no existen (tabla creada previamente minimalista)
            Schema::table('currency_rates', function (Blueprint $table) {
                if (!Schema::hasColumn('currency_rates','base')) { $table->string('base',10)->default('USD')->after('id'); }
                if (!Schema::hasColumn('currency_rates','quote')) { $table->string('quote',10)->default('VES')->after('base'); }
                if (!Schema::hasColumn('currency_rates','valid_from')) { $table->timestamp('valid_from')->nullable()->after('rate'); }
                if (!Schema::hasColumn('currency_rates','valid_to')) { $table->timestamp('valid_to')->nullable()->after('valid_from'); }
                if (!Schema::hasColumn('currency_rates','active')) { $table->boolean('active')->default(true)->after('valid_to'); }
            });
            // Intentar ampliar precisión de rate si la columna existe y no es DECIMAL(12,6)
            // Evitamos usar change() para no requerir doctrine/dbal; si se necesita más precisión a futuro, crear migración manual.
        }
    }

    public function down(): void
    {
        // Revertir sólo columnas agregadas (no eliminamos tabla completa)
        if (Schema::hasTable('currency_rates')) {
            Schema::table('currency_rates', function (Blueprint $table) {
                if (Schema::hasColumn('currency_rates','active')) { $table->dropColumn('active'); }
                if (Schema::hasColumn('currency_rates','valid_to')) { $table->dropColumn('valid_to'); }
                if (Schema::hasColumn('currency_rates','valid_from')) { $table->dropColumn('valid_from'); }
                if (Schema::hasColumn('currency_rates','quote')) { $table->dropColumn('quote'); }
                if (Schema::hasColumn('currency_rates','base')) { $table->dropColumn('base'); }
            });
        }
    }
};