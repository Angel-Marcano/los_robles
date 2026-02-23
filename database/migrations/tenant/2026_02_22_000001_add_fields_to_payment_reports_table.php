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
            if (!Schema::hasColumn('payment_reports', 'exchange_rate_used')) {
                $table->decimal('exchange_rate_used', 12, 6)->default(0)->after('amount_ves');
            }
            if (!Schema::hasColumn('payment_reports', 'status')) {
                $table->string('status')->default('reported')->after('exchange_rate_used');
            }
            if (!Schema::hasColumn('payment_reports', 'files')) {
                $table->json('files')->nullable()->after('status');
            }
            if (!Schema::hasColumn('payment_reports', 'notes')) {
                $table->text('notes')->nullable()->after('files');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('payment_reports')) {
            return;
        }

        Schema::table('payment_reports', function (Blueprint $table) {
            if (Schema::hasColumn('payment_reports', 'notes')) {
                $table->dropColumn('notes');
            }
            if (Schema::hasColumn('payment_reports', 'files')) {
                $table->dropColumn('files');
            }
            if (Schema::hasColumn('payment_reports', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('payment_reports', 'exchange_rate_used')) {
                $table->dropColumn('exchange_rate_used');
            }
        });
    }
};
