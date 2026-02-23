<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::table('invoices', function(Blueprint $table){ if(!Schema::hasColumn('invoices','paid_exchange_rate')){ $table->decimal('paid_exchange_rate',12,6)->nullable()->after('exchange_rate_used'); } }); } public function down(): void { Schema::table('invoices', function(Blueprint $table){ if(Schema::hasColumn('invoices','paid_exchange_rate')){ $table->dropColumn('paid_exchange_rate'); } }); } };
