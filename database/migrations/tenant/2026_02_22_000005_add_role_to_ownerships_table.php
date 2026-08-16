<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('ownerships')) {
            return;
        }

        Schema::table('ownerships', function (Blueprint $table) {
            if (!Schema::hasColumn('ownerships', 'role')) {
                $table->string('role')->default('owner')->after('user_id');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('ownerships')) {
            return;
        }

        Schema::table('ownerships', function (Blueprint $table) {
            if (Schema::hasColumn('ownerships', 'role')) {
                $table->dropColumn('role');
            }
        });
    }
};
