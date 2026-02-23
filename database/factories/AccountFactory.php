<?php
namespace Database\Factories; use Illuminate\Database\Eloquent\Factories\Factory; use App\Models\Account;
class AccountFactory extends Factory { protected $model=Account::class; public function definition(){ return ['owner_type'=>'Condominium','owner_id'=>1,'name'=>$this->faker->unique()->word(),'balance_usd'=>0,'balance_ves'=>0]; } }
