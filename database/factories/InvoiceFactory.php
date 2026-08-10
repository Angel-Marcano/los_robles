<?php

namespace Database\Factories;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        return [
            'number' => 'INV-' . $this->faker->unique()->numberBetween(1000, 9999),
            'period' => now()->format('Y-m'),
            'status' => 'pending',
            'total_usd' => 100.00,
            'total_ves' => 4000.00,
            'exchange_rate_used' => 40.00,
            'due_date' => now()->addDays(10),
            'owner_name' => $this->faker->name(),
            'owner_email' => $this->faker->safeEmail(),
        ];
    }
}
