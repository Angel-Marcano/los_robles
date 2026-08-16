<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Soporte para el fondo de reserva general del condominio:
 *  - reserve_funds.tower_id pasa a nullable (permite un fondo general con tower_id=null).
 *  - Se elimina unique('tower_id') y se recrea permitiendo NULL (un solo fondo general).
 *  - Se agrega condominium_id (nullable) para asociar el fondo general al condominio.
 *  - Se agrega reserve_type a reserve_fund_movements e invoice_items ('tower'|'general').
 */
return new class extends Migration {
    public function up(): void
    {
        // 1. reserve_funds: tower_id nullable + condominium_id
        if (Schema::hasTable('reserve_funds')) {
            // Eliminar la foreign key y el unique actuales sobre tower_id
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $foreignKeys = $sm->listTableForeignKeys('reserve_funds');
            foreach ($foreignKeys as $fk) {
                if (in_array('tower_id', $fk->getLocalColumns(), true)) {
                    Schema::table('reserve_funds', function (Blueprint $table) use ($fk) {
                        $table->dropForeign($fk->getName());
                    });
                }
            }

            Schema::table('reserve_funds', function (Blueprint $table) {
                // drop unique sobre tower_id si existe
            });
            // drop unique index by name (Laravel lo nombra 'reserve_funds_tower_id_unique')
            try {
                Schema::getConnection()->statement('ALTER TABLE reserve_funds DROP INDEX reserve_funds_tower_id_unique');
            } catch (\Throwable $e) {
                // puede no existir; ignorar
            }

            // Hacer tower_id nullable
            Schema::table('reserve_funds', function (Blueprint $table) {
                $table->unsignedBigInteger('tower_id')->nullable()->change();
            });

            // Recrear foreign key nullable
            Schema::table('reserve_funds', function (Blueprint $table) {
                $table->foreign('tower_id')->references('id')->on('towers')->cascadeOnDelete();
            });

            // Agregar condominium_id (nullable, sin foreign porque vive en BD master)
            if (!Schema::hasColumn('reserve_funds', 'condominium_id')) {
                Schema::table('reserve_funds', function (Blueprint $table) {
                    $table->unsignedBigInteger('condominium_id')->nullable()->after('tower_id');
                    $table->index('condominium_id');
                });
            }

            // Unique compuesto: un fondo por torre + un fondo general (tower_id=null) por condominio
            // MySQL permite múltiples NULLs en unique, así que el general se controla por app (forCondominium).
            // Recrear unique sobre tower_id (acepta un solo NULL en MySQL/SQLite).
            Schema::getConnection()->statement('ALTER TABLE reserve_funds ADD UNIQUE INDEX reserve_funds_tower_id_unique (tower_id)');
        }

        // 2. reserve_fund_movements: agregar reserve_type
        if (Schema::hasTable('reserve_fund_movements') && !Schema::hasColumn('reserve_fund_movements', 'reserve_type')) {
            Schema::table('reserve_fund_movements', function (Blueprint $table) {
                $table->string('reserve_type', 20)->nullable()->after('source');
                $table->index('reserve_type');
            });
        }

        // 3. invoice_items: agregar reserve_type
        if (Schema::hasTable('invoice_items') && !Schema::hasColumn('invoice_items', 'reserve_type')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                $table->string('reserve_type', 20)->nullable()->after('is_reserve');
                $table->index('reserve_type');
            });
        }
    }

    public function down(): void
    {
        // Revertir invoice_items
        if (Schema::hasTable('invoice_items') && Schema::hasColumn('invoice_items', 'reserve_type')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                $table->dropIndex(['reserve_type']);
                $table->dropColumn('reserve_type');
            });
        }

        // Revertir reserve_fund_movements
        if (Schema::hasTable('reserve_fund_movements') && Schema::hasColumn('reserve_fund_movements', 'reserve_type')) {
            Schema::table('reserve_fund_movements', function (Blueprint $table) {
                $table->dropIndex(['reserve_type']);
                $table->dropColumn('reserve_type');
            });
        }

        // Revertir reserve_funds
        if (Schema::hasTable('reserve_funds')) {
            // drop unique recreado
            try {
                Schema::getConnection()->statement('ALTER TABLE reserve_funds DROP INDEX reserve_funds_tower_id_unique');
            } catch (\Throwable $e) {
                // ignorar
            }

            if (Schema::hasColumn('reserve_funds', 'condominium_id')) {
                Schema::table('reserve_funds', function (Blueprint $table) {
                    $table->dropIndex(['condominium_id']);
                    $table->dropColumn('condominium_id');
                });
            }

            // drop foreign nullable
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $foreignKeys = $sm->listTableForeignKeys('reserve_funds');
            foreach ($foreignKeys as $fk) {
                if (in_array('tower_id', $fk->getLocalColumns(), true)) {
                    Schema::table('reserve_funds', function (Blueprint $table) use ($fk) {
                        $table->dropForeign($fk->getName());
                    });
                }
            }

            // tower_id not null de nuevo
            Schema::table('reserve_funds', function (Blueprint $table) {
                $table->unsignedBigInteger('tower_id')->nullable(false)->change();
            });

            Schema::table('reserve_funds', function (Blueprint $table) {
                $table->foreign('tower_id')->references('id')->on('towers')->cascadeOnDelete();
            });

            // unique original
            Schema::getConnection()->statement('ALTER TABLE reserve_funds ADD UNIQUE INDEX reserve_funds_tower_id_unique (tower_id)');
        }
    }
};