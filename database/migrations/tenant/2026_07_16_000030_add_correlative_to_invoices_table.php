<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('invoices')) {
            return;
        }

        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'correlative')) {
                $table->unsignedInteger('correlative')->nullable()->after('number');
                $table->unique('correlative');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('invoices')) {
            return;
        }

        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'correlative')) {
                try {
                    $table->dropUnique(['correlative']);
                } catch (\Throwable $e) {
                    // Ignore if unique does not exist.
                }
                $table->dropColumn('correlative');
            }
        });
    }
};
