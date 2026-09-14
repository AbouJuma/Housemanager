<?php

namespace App\Modules\Properties\Database\Factories;

use App\Modules\Properties\Models\Building;
use App\Modules\Properties\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Unit>
 */
class UnitFactory extends Factory
{
    protected $model = Unit::class;

    public function definition(): array
    {
        return [
            'building_id' => Building::factory(),
            'unit_number' => strtoupper(fake()->bothify('##?')),
            'type' => fake()->randomElement(['apartment', 'studio', 'office', 'shop', 'warehouse_space', 'room', 'other']),
            'status' => 'vacant',
            'bedrooms' => fake()->numberBetween(0, 4),
            'bathrooms' => fake()->numberBetween(1, 3),
            'area' => fake()->randomFloat(2, 20, 300),
            'area_unit' => 'sqm',
            'rent_amount' => fake()->randomFloat(2, 100, 5000),
        ];
    }

    public function occupied(): static
    {
        return $this->state(fn () => ['status' => 'occupied']);
    }

    public function vacant(): static
    {
        return $this->state(fn () => ['status' => 'vacant']);
    }
}
