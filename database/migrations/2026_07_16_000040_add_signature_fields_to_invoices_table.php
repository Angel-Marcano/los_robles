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
            if (!Schema::hasColumn('invoices', 'invoice_signature')) {
                $table->string('invoice_signature', 64)->nullable()->after('paid_exchange_rate');
            }
            if (!Schema::hasColumn('invoices', 'signed_at')) {
                $table->timestamp('signed_at')->nullable()->after('invoice_signature');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('invoices')) {
            return;
        }

        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'signed_at')) {
                $table->dropColumn('signed_at');
            }
            if (Schema::hasColumn('invoices', 'invoice_signature')) {
                $table->dropColumn('invoice_signature');
            }
        });
    }
};
