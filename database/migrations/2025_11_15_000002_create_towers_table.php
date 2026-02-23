<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up():void { if(!Schema::hasTable('towers')){ Schema::create('towers',function(Blueprint $table){ $table->id(); $table->foreignId('condominium_id')->constrained('condominiums')->cascadeOnDelete(); $table->string('name'); $table->boolean('active')->default(true); $table->timestamps(); }); } else { // intentar agregar FK si no existe
			$exists = \DB::table('information_schema.KEY_COLUMN_USAGE')
				->where('TABLE_SCHEMA', env('DB_DATABASE'))
				->where('TABLE_NAME','towers')
				->where('COLUMN_NAME','condominium_id')
				->exists();
			if(!$exists){ \DB::statement('ALTER TABLE towers ADD CONSTRAINT towers_condominium_id_foreign FOREIGN KEY (condominium_id) REFERENCES condominiums(id) ON DELETE CASCADE'); }
		} } public function down():void { Schema::dropIfExists('towers'); }};
