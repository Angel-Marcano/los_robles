<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('owner_name')->nullable()->after('apartment_id');
            $table->string('owner_email')->nullable()->after('owner_name');
            $table->string('owner_document')->nullable()->after('owner_email');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['owner_name', 'owner_email', 'owner_document']);
        });
    }
};
