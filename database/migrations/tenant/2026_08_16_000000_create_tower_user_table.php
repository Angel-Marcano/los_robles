<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla pivote tower_user: asocia usuarios (típicamente tower_admin)
 * con las torres que administran.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('tower_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tower_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'tower_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tower_user');
    }
};