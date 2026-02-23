<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up():void { Schema::create('apartments',function(Blueprint $table){ $table->id(); $table->foreignId('tower_id')->constrained()->cascadeOnDelete(); $table->string('code'); $table->boolean('active')->default(true); $table->timestamps(); $table->unique(['tower_id','code']); }); } public function down():void { Schema::dropIfExists('apartments'); }};
