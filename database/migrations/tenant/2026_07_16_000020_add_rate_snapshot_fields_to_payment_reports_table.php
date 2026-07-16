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
            if (!Schema::hasColumn('payment_reports', 'exchange_rate_valid_from')) {
                $table->timestamp('exchange_rate_valid_from')->nullable()->after('exchange_rate_used');
            }
            if (!Schema::hasColumn('payment_reports', 'currency_rate_id')) {
                $table->unsignedBigInteger('currency_rate_id')->nullable()->after('exchange_rate_valid_from');
                $table->index('currency_rate_id');
            }
        });

        if (Schema::hasTable('currency_rates')) {
            Schema::table('payment_reports', function (Blueprint $table) {
                try {
                    $table->foreign('currency_rate_id')->references('id')->on('currency_rates')->nullOnDelete();
                } catch (\Throwable $e) {
                    // Ignore if FK already exists or engine does not support it.
                }
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('payment_reports')) {
            return;
        }

        Schema::table('payment_reports', function (Blueprint $table) {
            try {
                $table->dropForeign(['currency_rate_id']);
            } catch (\Throwable $e) {
                // Ignore if FK does not exist.
            }
            if (Schema::hasColumn('payment_reports', 'currency_rate_id')) {
                try {
                    $table->dropIndex(['currency_rate_id']);
                } catch (\Throwable $e) {
                    // Ignore if index does not exist.
                }
                $table->dropColumn('currency_rate_id');
            }
            if (Schema::hasColumn('payment_reports', 'exchange_rate_valid_from')) {
                $table->dropColumn('exchange_rate_valid_from');
            }
        });
    }
};
