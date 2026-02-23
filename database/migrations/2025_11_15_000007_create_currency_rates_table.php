<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up():void { Schema::create('currency_rates',function(Blueprint $table){ $table->id(); $table->string('base'); $table->string('quote'); $table->decimal('rate',12,6); $table->timestamp('valid_from'); $table->timestamp('valid_to')->nullable(); $table->boolean('active')->default(true); $table->timestamps(); }); } public function down():void { Schema::dropIfExists('currency_rates'); }};
