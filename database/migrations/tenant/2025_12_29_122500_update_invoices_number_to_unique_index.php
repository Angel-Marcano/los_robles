<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Convertir el índice de 'number' a único
        if (Schema::hasColumn('invoices', 'number')) {
            Schema::table('invoices', function (Blueprint $table) {
                // El índice normal probablemente se llama 'invoices_number_index'
                try { $table->dropIndex(['number']); } catch (\Throwable $e) { /* ignorar si no existe */ }
                $table->unique('number');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('invoices', 'number')) {
            Schema::table('invoices', function (Blueprint $table) {
                // Revertir a índice no único
                try { $table->dropUnique(['number']); } catch (\Throwable $e) { /* ignorar si no existe */ }
                $table->index('number');
            });
        }
    }
};
