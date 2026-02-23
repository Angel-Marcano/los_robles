<?php
namespace Database\Seeders; use Illuminate\Database\Seeder; use App\Models\{Condominium,Tower,Apartment};
class InitialStructureSeeder extends Seeder { public function run(): void { $condo=Condominium::firstOrCreate(['name'=>'Los Robles']); $towers=['Torre A','Torre B','Torre C']; foreach($towers as $tName){ $tower=Tower::firstOrCreate(['name'=>$tName,'condominium_id'=>$condo->id]); for($i=1;$i<=4;$i++){ Apartment::firstOrCreate(['tower_id'=>$tower->id,'code'=>($tName==='Torre A'?'A':($tName==='Torre B'?'B':'C')).$i]); } } }}
