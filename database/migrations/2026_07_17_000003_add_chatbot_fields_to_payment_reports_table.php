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
            if (!Schema::hasColumn('payment_reports', 'apartment_id')) {
                $table->unsignedBigInteger('apartment_id')->nullable()->after('invoice_id')->index();
            }
            if (!Schema::hasColumn('payment_reports', 'reported_by')) {
                $table->unsignedBigInteger('reported_by')->nullable()->after('user_id')->index();
            }
            if (!Schema::hasColumn('payment_reports', 'payment_method')) {
                $table->string('payment_method', 64)->nullable()->after('reported_by');
            }
            if (!Schema::hasColumn('payment_reports', 'reference_number')) {
                $table->string('reference_number', 128)->nullable()->after('payment_method');
            }
            if (!Schema::hasColumn('payment_reports', 'paid_at')) {
                $table->date('paid_at')->nullable()->after('reference_number');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('payment_reports')) {
            return;
        }

        Schema::table('payment_reports', function (Blueprint $table) {
            if (Schema::hasColumn('payment_reports', 'paid_at')) {
                $table->dropColumn('paid_at');
            }
            if (Schema::hasColumn('payment_reports', 'reference_number')) {
                $table->dropColumn('reference_number');
            }
            if (Schema::hasColumn('payment_reports', 'payment_method')) {
                $table->dropColumn('payment_method');
            }
            if (Schema::hasColumn('payment_reports', 'reported_by')) {
                $table->dropColumn('reported_by');
            }
            if (Schema::hasColumn('payment_reports', 'apartment_id')) {
                $table->dropColumn('apartment_id');
            }
        });
    }
};
