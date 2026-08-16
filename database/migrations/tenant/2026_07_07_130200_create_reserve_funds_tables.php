<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (!Schema::hasTable('reserve_funds')) {
            Schema::create('reserve_funds', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tower_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->decimal('balance_usd', 14, 2)->default(0);
                $table->decimal('balance_ves', 14, 2)->default(0);
                $table->timestamps();
                $table->unique('tower_id');
            });
        }

        if (!Schema::hasTable('reserve_fund_movements')) {
            Schema::create('reserve_fund_movements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('reserve_fund_id')->constrained()->cascadeOnDelete();
                $table->string('direction');            // income | expense
                $table->string('source')->default('manual'); // invoice | manual | adjustment
                $table->unsignedBigInteger('invoice_id')->nullable();
                $table->unsignedBigInteger('apartment_id')->nullable();
                $table->decimal('amount_usd', 14, 2)->default(0);
                $table->decimal('amount_ves', 14, 2)->default(0);
                $table->decimal('exchange_rate', 12, 6)->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->timestamps();
                $table->index('invoice_id');
            });
        }
    }

    public function down(): void {
        Schema::dropIfExists('reserve_fund_movements');
        Schema::dropIfExists('reserve_funds');
    }
};
