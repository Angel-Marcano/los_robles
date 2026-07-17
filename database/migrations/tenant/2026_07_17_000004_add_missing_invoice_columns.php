<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('invoices')) {
            return;
        }

        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'number')) {
                $table->string('number')->nullable()->after('id');
            }
            if (!Schema::hasColumn('invoices', 'parent_id')) {
                $table->unsignedBigInteger('parent_id')->nullable()->after('number');
                $table->index('parent_id');
            }
            if (!Schema::hasColumn('invoices', 'apartment_id')) {
                $table->unsignedBigInteger('apartment_id')->nullable()->after('parent_id');
                $table->index('apartment_id');
            }
            if (!Schema::hasColumn('invoices', 'paid_exchange_rate')) {
                $table->decimal('paid_exchange_rate', 12, 6)->nullable()->after('paid_at');
            }
            if (!Schema::hasColumn('invoices', 'owner_name')) {
                $table->string('owner_name')->nullable()->after('reminder_sent_at');
            }
            if (!Schema::hasColumn('invoices', 'owner_email')) {
                $table->string('owner_email')->nullable()->after('owner_name');
            }
            if (!Schema::hasColumn('invoices', 'owner_document')) {
                $table->string('owner_document')->nullable()->after('owner_email');
            }
            if (Schema::hasColumn('invoices', 'condominium_id')) {
                DB::statement('ALTER TABLE invoices MODIFY condominium_id BIGINT UNSIGNED NULL');
            }
        });

        if (Schema::hasTable('apartments')) {
            Schema::table('invoices', function (Blueprint $table) {
                try {
                    $table->foreign('apartment_id')->references('id')->on('apartments')->nullOnDelete();
                } catch (\Throwable $e) {
                    // Ignore if FK already exists or engine does not support it.
                }
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('invoices')) {
            return;
        }

        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'owner_document')) {
                $table->dropColumn('owner_document');
            }
            if (Schema::hasColumn('invoices', 'owner_email')) {
                $table->dropColumn('owner_email');
            }
            if (Schema::hasColumn('invoices', 'owner_name')) {
                $table->dropColumn('owner_name');
            }
            if (Schema::hasColumn('invoices', 'paid_exchange_rate')) {
                $table->dropColumn('paid_exchange_rate');
            }
            if (Schema::hasColumn('invoices', 'apartment_id')) {
                try {
                    $table->dropForeign(['apartment_id']);
                } catch (\Throwable $e) {
                    // Ignore if FK does not exist.
                }
                $table->dropColumn('apartment_id');
            }
            if (Schema::hasColumn('invoices', 'parent_id')) {
                $table->dropColumn('parent_id');
            }
            if (Schema::hasColumn('invoices', 'number')) {
                $table->dropColumn('number');
            }
        });
    }
};
