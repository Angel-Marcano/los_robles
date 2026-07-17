<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('support_tickets')) {
            return;
        }

        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('condominium_id')->nullable()->index();
            $table->string('category', 64); // billing, maintenance, general, etc.
            $table->string('priority', 20)->default('medium'); // low, medium, high
            $table->text('description');
            $table->string('status', 20)->default('open'); // open, in_progress, resolved, closed
            $table->unsignedBigInteger('assigned_to')->nullable()->index();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('support_tickets')) {
            Schema::dropIfExists('support_tickets');
        }
    }
};
