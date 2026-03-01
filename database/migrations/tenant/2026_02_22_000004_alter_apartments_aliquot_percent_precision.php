<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('apartments') || !Schema::hasColumn('apartments', 'aliquot_percent')) {
            return;
        }

        // MySQL: change DECIMAL precision without requiring doctrine/dbal
        DB::statement("ALTER TABLE apartments MODIFY aliquot_percent DECIMAL(12,8) NOT NULL DEFAULT 0");
    }

    public function down(): void
    {
        if (!Schema::hasTable('apartments') || !Schema::hasColumn('apartments', 'aliquot_percent')) {
            return;
        }

        DB::statement("ALTER TABLE apartments MODIFY aliquot_percent DECIMAL(8,4) NOT NULL DEFAULT 0.0000");
    }
};
