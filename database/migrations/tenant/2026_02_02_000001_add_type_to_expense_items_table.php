<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('expense_items')) {
            return;
        }

        Schema::table('expense_items', function (Blueprint $table) {
            if (!Schema::hasColumn('expense_items', 'type')) {
                // Keep it nullable first to avoid issues if the table has rows.
                // We'll backfill to 'fixed' via maintenance command and you can enforce NOT NULL later if desired.
                $table->string('type')->nullable()->after('name');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('expense_items')) {
            return;
        }

        Schema::table('expense_items', function (Blueprint $table) {
            if (Schema::hasColumn('expense_items', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};
