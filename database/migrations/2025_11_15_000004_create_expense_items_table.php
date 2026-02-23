<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up():void { Schema::create('expense_items',function(Blueprint $table){ $table->id(); $table->foreignId('condominium_id')->constrained('condominiums')->cascadeOnDelete(); $table->string('name'); $table->enum('type',['fixed','aliquot']); $table->boolean('active')->default(true); $table->timestamps(); }); } public function down():void { Schema::dropIfExists('expense_items'); }};
