<?php

namespace App\Modules\Sales\Database\Factories;

use App\Modules\Sales\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

class SaleFactory extends Factory
{
    protected $model = Sale::class;

    public function definition(): array
    {
        return [
            'sale_date' => $this->faker->date(),
            'total_price' => $this->faker->randomFloat(2, 10000, 500000),
            'down_payment' => $this->faker->randomFloat(2, 0, 50000),
            'installment_period' => $this->faker->numberBetween(6, 60),
            'installment_amount' => $this->faker->randomFloat(2, 1000, 10000),
            'status' => $this->faker->randomElement(['pending', 'active', 'completed', 'cancelled']),
            'notes' => $this->faker->optional()->text(),
        ];
    }
}
