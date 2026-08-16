<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('comments')) {
            return;
        }

        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->morphs('commentable'); // commentable_id + commentable_type
            $table->unsignedBigInteger('user_id')->index();
            $table->text('message');
            $table->boolean('is_internal')->default(false); // true = solo admin puede ver
            $table->timestamps();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('comments')) {
            Schema::dropIfExists('comments');
        }
    }
};
