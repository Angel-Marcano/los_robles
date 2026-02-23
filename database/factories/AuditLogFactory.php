<?php
namespace Database\Factories; use Illuminate\Database\Eloquent\Factories\Factory; use App\Models\AuditLog;
class AuditLogFactory extends Factory { protected $model=AuditLog::class; public function definition(){ return [ 'user_id'=>1,'action'=>$this->faker->randomElement(['created','updated','deleted']), 'entity_type'=>$this->faker->randomElement(['Invoice','Account','User']), 'entity_id'=>$this->faker->numberBetween(1,50), 'changes'=>['field'=>'value'], 'ip'=>$this->faker->ipv4 ]; } }
