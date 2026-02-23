<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('payment_reports')) {
            return;
        }

        Schema::table('payment_reports', function (Blueprint $table) {
            if (!Schema::hasColumn('payment_reports', 'usd_equivalent')) {
                $table->decimal('usd_equivalent', 12, 2)->nullable()->after('exchange_rate_used');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('payment_reports')) {
            return;
        }

        Schema::table('payment_reports', function (Blueprint $table) {
            if (Schema::hasColumn('payment_reports', 'usd_equivalent')) {
                $table->dropColumn('usd_equivalent');
            }
        });
    }
};
