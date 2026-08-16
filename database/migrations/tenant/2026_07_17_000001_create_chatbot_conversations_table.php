<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('chatbot_conversations')) {
            return;
        }

        Schema::create('chatbot_conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('condominium_id')->nullable()->index();
            $table->string('channel', 20)->default('web');
            $table->string('session_id', 64)->index();
            $table->string('intent', 64)->nullable()->index();
            $table->string('prompt_version', 32)->nullable();
            $table->text('input_raw')->nullable();
            $table->text('input_sanitized')->nullable();
            $table->longText('output_raw')->nullable();
            $table->longText('output_sanitized')->nullable();
            $table->json('tools_called')->nullable();
            $table->json('actions_executed')->nullable();
            $table->json('context')->nullable();
            $table->unsignedInteger('tokens_input')->default(0);
            $table->unsignedInteger('tokens_output')->default(0);
            $table->string('model', 64)->nullable();
            $table->unsignedInteger('duration_ms')->default(0);
            $table->boolean('needs_human')->default(false)->index();
            $table->boolean('is_action_pending')->default(false)->index();
            $table->json('pending_action')->nullable();
            $table->timestamp('pending_action_expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'session_id', 'created_at']);
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('chatbot_conversations')) {
            Schema::dropIfExists('chatbot_conversations');
        }
    }
};
