<?php
use Illuminate\Database\Migrations\Migration; 
use Illuminate\Database\Schema\Blueprint; 
use Illuminate\Support\Facades\Schema;
return new class extends Migration { 
    public function up(): void { 
        Schema::create('payment_reports', function (Blueprint $table) { 
            $table->id(); 
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete(); 
            $table->unsignedBigInteger('user_id'); 
            $table->decimal('amount_usd', 12, 2)->default(0); 
            $table->decimal('amount_ves', 14, 2)->default(0); 
            $table->decimal('exchange_rate_used', 12, 6)->default(0); 
            $table->string('status')->default('reported'); 
            $table->json('files')->nullable(); 
            $table->text('notes')->nullable(); 
            $table->timestamps(); 
        }); 
    } 
    public function down(): void { 
        Schema::dropIfExists('payment_reports'); 
    }
};
