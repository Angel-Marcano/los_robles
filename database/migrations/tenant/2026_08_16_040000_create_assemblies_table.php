<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Asambleas / votaciones
        Schema::create('assemblies', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('scope', ['condo', 'tower'])->default('condo');
            $table->json('tower_ids')->nullable(); // null = todas las torres
            $table->enum('vote_type', ['public', 'secret'])->default('public');
            $table->enum('quorum_type', ['none', 'simple', 'qualified'])->default('simple');
            $table->decimal('quorum_value', 5, 2)->default(50.00); // porcentaje mínimo
            $table->enum('weight_mode', ['equal', 'aliquot'])->default('equal'); // 1 voto por apto o por alícuota
            $table->timestamp('closes_at')->nullable();
            $table->enum('status', ['draft', 'open', 'closed'])->default('draft');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        // Opciones de votación
        Schema::create('assembly_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assembly_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->integer('sort_order')->default(0);
        });

        // Votos emitidos
        Schema::create('assembly_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assembly_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('option_id')->constrained('assembly_options')->cascadeOnDelete();
            $table->decimal('weight', 10, 4)->default(1.0000);
            $table->timestamp('voted_at')->useCurrent();
            $table->unique(['assembly_id', 'user_id']); // un voto por usuario por asamblea
        });

        // Log de notificaciones enviadas
        Schema::create('assembly_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assembly_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['summon', 'reminder', 'result']);
            $table->timestamp('sent_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assembly_notifications');
        Schema::dropIfExists('assembly_votes');
        Schema::dropIfExists('assembly_options');
        Schema::dropIfExists('assemblies');
    }
};