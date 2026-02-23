<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::table('users', function(Blueprint $table){ $table->string('first_name')->nullable()->after('name'); $table->string('last_name')->nullable()->after('first_name'); $table->string('document_type')->nullable()->after('last_name'); $table->string('document_number')->nullable()->after('document_type'); }); }
public function down(): void { Schema::table('users', function(Blueprint $table){ $table->dropColumn(['first_name','last_name','document_type','document_number']); }); } };
