<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema; use Illuminate\Support\Facades\DB;
return new class extends Migration {
    public function up(): void {
        Schema::table('apartments', function (Blueprint $table) {
            if(!Schema::hasColumn('apartments','condominium_id')){
                $table->unsignedBigInteger('condominium_id')->nullable()->after('tower_id');
            }
        });
        // Poblar condominium_id usando relación tower->condominium
        DB::statement('UPDATE apartments a JOIN towers t ON a.tower_id = t.id SET a.condominium_id = t.condominium_id');
    // Convertir a NOT NULL sin DBAL (ALTER manual)
    DB::statement('ALTER TABLE apartments MODIFY condominium_id BIGINT UNSIGNED NOT NULL');
        // Eliminar unique anterior si existe (tower_id, code)
        try { DB::statement('ALTER TABLE apartments DROP INDEX apartments_tower_id_code_unique'); } catch(\Exception $e) {}
        // Crear unique correcto por condominio
        DB::statement('CREATE UNIQUE INDEX apartments_condo_code_unique ON apartments(condominium_id, code)');
    }
    public function down(): void {
        try { DB::statement('DROP INDEX apartments_condo_code_unique ON apartments'); } catch(\Exception $e) {}
        Schema::table('apartments', function (Blueprint $table) { if(Schema::hasColumn('apartments','condominium_id')){ $table->dropColumn('condominium_id'); } });
    }
};
