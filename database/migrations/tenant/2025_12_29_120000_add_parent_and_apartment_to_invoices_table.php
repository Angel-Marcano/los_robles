<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('invoices', 'parent_id')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->unsignedBigInteger('parent_id')->nullable()->after('id');
                $table->unsignedBigInteger('apartment_id')->nullable()->after('tower_id');
                $table->index('parent_id');
                $table->index('apartment_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices','parent_id')) {
                $table->dropIndex(['parent_id']);
                $table->dropColumn('parent_id');
            }
            if (Schema::hasColumn('invoices','apartment_id')) {
                $table->dropIndex(['apartment_id']);
                $table->dropColumn('apartment_id');
            }
        });
    }
};
