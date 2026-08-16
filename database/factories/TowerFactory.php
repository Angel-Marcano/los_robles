<?php

namespace Database\Factories;

use App\Models\Tower;
use Illuminate\Database\Eloquent\Factories\Factory;

class TowerFactory extends Factory
{
    protected $model = Tower::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
            'condominium_id' => \App\Models\Condominium::factory(),
            'active' => true,
        ];
    }
}
