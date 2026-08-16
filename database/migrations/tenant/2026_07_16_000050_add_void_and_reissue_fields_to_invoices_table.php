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
            if (!Schema::hasColumn('invoices', 'voided_at')) {
                $table->timestamp('voided_at')->nullable()->after('signed_at');
            }
            if (!Schema::hasColumn('invoices', 'void_reason')) {
                $table->text('void_reason')->nullable()->after('voided_at');
            }
            if (!Schema::hasColumn('invoices', 'reissued_by')) {
                $table->unsignedBigInteger('reissued_by')->nullable()->after('void_reason');
            }
            if (!Schema::hasColumn('invoices', 'reissued_to_invoice_id')) {
                $table->unsignedBigInteger('reissued_to_invoice_id')->nullable()->after('reissued_by');
                $table->index('reissued_to_invoice_id');
            }
            if (!Schema::hasColumn('invoices', 'reissued_from_invoice_id')) {
                $table->unsignedBigInteger('reissued_from_invoice_id')->nullable()->after('reissued_to_invoice_id');
                $table->index('reissued_from_invoice_id');
            }
            if (!Schema::hasColumn('invoices', 'reminder_sent_at')) {
                $table->timestamp('reminder_sent_at')->nullable()->after('reissued_from_invoice_id');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('invoices')) {
            return;
        }

        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'reissued_from_invoice_id')) {
                try {
                    $table->dropIndex(['reissued_from_invoice_id']);
                } catch (\Throwable $e) {
                    // Ignore if index does not exist.
                }
                $table->dropColumn('reissued_from_invoice_id');
            }
            if (Schema::hasColumn('invoices', 'reissued_to_invoice_id')) {
                try {
                    $table->dropIndex(['reissued_to_invoice_id']);
                } catch (\Throwable $e) {
                    // Ignore if index does not exist.
                }
                $table->dropColumn('reissued_to_invoice_id');
            }
            if (Schema::hasColumn('invoices', 'reissued_by')) {
                $table->dropColumn('reissued_by');
            }
            if (Schema::hasColumn('invoices', 'reminder_sent_at')) {
                $table->dropColumn('reminder_sent_at');
            }
            if (Schema::hasColumn('invoices', 'void_reason')) {
                $table->dropColumn('void_reason');
            }
            if (Schema::hasColumn('invoices', 'voided_at')) {
                $table->dropColumn('voided_at');
            }
        });
    }
};
