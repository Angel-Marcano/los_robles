<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Nota: En BD tenant no duplicamos la tabla condominiums; el condominio se asume único.
        // Si necesitas datos del condominio dentro del tenant, podrías crear una tabla 'condominium_profile'.

        if (!Schema::hasTable('towers')) {
            Schema::create('towers', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->boolean('active')->default(true);
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('apartments')) {
            Schema::create('apartments', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('tower_id');
                $table->string('code');
                $table->boolean('active')->default(true);
                $table->timestamps();
                $table->foreign('tower_id')->references('id')->on('towers')->onDelete('cascade');
            });
        }
        if (!Schema::hasTable('expense_items')) {
            Schema::create('expense_items', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->string('type')->nullable();
                $table->decimal('amount_usd',10,2)->default(0);
                $table->boolean('active')->default(true);
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('invoices')) {
            Schema::create('invoices', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('tower_id')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->string('period');
                $table->date('due_date')->nullable();
                $table->string('status')->default('draft');
                $table->string('late_fee_type')->nullable();
                $table->string('late_fee_scope')->nullable();
                $table->decimal('late_fee_value',10,2)->nullable();
                $table->decimal('late_fee_accrued_usd',10,2)->default(0);
                $table->decimal('late_fee_accrued_ves',10,2)->default(0);
                $table->decimal('exchange_rate_used',10,2)->default(0);
                $table->decimal('total_usd',12,2)->default(0);
                $table->decimal('total_ves',12,2)->default(0);
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->foreign('tower_id')->references('id')->on('towers')->nullOnDelete();
            });
        }
        if (!Schema::hasTable('invoice_items')) {
            Schema::create('invoice_items', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('invoice_id');
                $table->unsignedBigInteger('apartment_id')->nullable();
                $table->unsignedBigInteger('expense_item_id')->nullable();
                $table->decimal('base_amount_usd',10,2)->default(0); // monto base (pool o fijo)
                $table->boolean('distributed')->default(false); // true si se distribuye (alícuota)
                $table->decimal('subtotal_usd',10,2)->default(0);
                $table->decimal('subtotal_ves',10,2)->default(0);
                $table->timestamps();
                $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');
                $table->foreign('apartment_id')->references('id')->on('apartments')->nullOnDelete();
                $table->foreign('expense_item_id')->references('id')->on('expense_items')->nullOnDelete();
            });
        }
        if (!Schema::hasTable('accounts')) {
            Schema::create('accounts', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('owner_type')->nullable();
                $table->unsignedBigInteger('owner_id')->default(0);
                $table->string('name');
                $table->decimal('balance_usd',12,2)->default(0);
                $table->decimal('balance_ves',12,2)->default(0);
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('account_movements')) {
            Schema::create('account_movements', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('account_id');
                $table->string('type');
                $table->decimal('amount_usd',12,2)->default(0);
                $table->decimal('amount_ves',12,2)->default(0);
                $table->text('description')->nullable();
                $table->timestamps();
                $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            });
        }
        if (!Schema::hasTable('ownerships')) {
            Schema::create('ownerships', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('apartment_id');
                $table->unsignedBigInteger('user_id');
                $table->timestamps();
                $table->foreign('apartment_id')->references('id')->on('apartments')->onDelete('cascade');
            });
        }
        if (!Schema::hasTable('payment_reports')) {
            Schema::create('payment_reports', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('invoice_id');
                $table->unsignedBigInteger('user_id');
                $table->decimal('amount_usd',12,2)->default(0);
                $table->decimal('amount_ves',12,2)->default(0);
                $table->string('method')->nullable();
                $table->timestamps();
                $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');
            });
        }
        if (!Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('action');
                $table->string('auditable_type')->nullable();
                $table->unsignedBigInteger('auditable_id')->nullable();
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('currency_rates')) {
            Schema::create('currency_rates', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->decimal('rate',10,2);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('currency_rates');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('payment_reports');
        Schema::dropIfExists('ownerships');
        Schema::dropIfExists('account_movements');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('expense_items');
        Schema::dropIfExists('apartments');
        Schema::dropIfExists('towers');
    }
};
