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

        if (!Schema::hasColumn('invoices', 'paid_exchange_rate')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->decimal('paid_exchange_rate', 12, 6)->default(0)->after('paid_at');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('invoices')) {
            return;
        }

        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'paid_exchange_rate')) {
                $table->dropColumn('paid_exchange_rate');
            }
        });
    }
};
