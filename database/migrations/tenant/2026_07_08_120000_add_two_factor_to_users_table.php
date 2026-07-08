<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'two_factor_method')) {
                $table->string('two_factor_method', 10)->nullable()->after('active'); // null=off | email | totp
            }
            if (!Schema::hasColumn('users', 'two_factor_secret')) {
                $table->text('two_factor_secret')->nullable()->after('two_factor_method'); // secreto TOTP encriptado
            }
            if (!Schema::hasColumn('users', 'two_factor_code')) {
                $table->string('two_factor_code')->nullable()->after('two_factor_secret'); // hash del código email
            }
            if (!Schema::hasColumn('users', 'two_factor_code_expires_at')) {
                $table->timestamp('two_factor_code_expires_at')->nullable()->after('two_factor_code');
            }
            if (!Schema::hasColumn('users', 'two_factor_confirmed_at')) {
                $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_code_expires_at');
            }
        });
    }

    public function down(): void {
        Schema::table('users', function (Blueprint $table) {
            foreach (['two_factor_method','two_factor_secret','two_factor_code','two_factor_code_expires_at','two_factor_confirmed_at'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
