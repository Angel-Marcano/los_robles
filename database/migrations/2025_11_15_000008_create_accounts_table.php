<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up():void { Schema::create('accounts',function(Blueprint $table){ $table->id(); $table->string('owner_type'); $table->unsignedBigInteger('owner_id'); $table->string('name'); $table->decimal('balance_usd',12,2)->default(0); $table->decimal('balance_ves',14,2)->default(0); $table->timestamps(); $table->index(['owner_type','owner_id']); }); } public function down():void { Schema::dropIfExists('accounts'); }};
