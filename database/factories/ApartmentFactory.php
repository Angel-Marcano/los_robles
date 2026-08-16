<?php

namespace Database\Factories;

use App\Models\Apartment;
use Illuminate\Database\Eloquent\Factories\Factory;

class ApartmentFactory extends Factory
{
    protected $model = Apartment::class;

    public function definition(): array
    {
        $tower = \App\Models\Tower::factory()->create();
        return [
            'tower_id' => $tower->id,
            'condominium_id' => $tower->condominium_id,
            'code' => $this->faker->unique()->numberBetween(100, 999),
            'active' => true,
        ];
    }
}
