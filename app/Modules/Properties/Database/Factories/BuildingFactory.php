<?php

namespace App\Modules\Properties\Database\Factories;

use App\Modules\Properties\Models\Building;
use App\Modules\Properties\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Building>
 */
class BuildingFactory extends Factory
{
    protected $model = Building::class;

    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'name' => 'Block '.fake()->randomLetter().strtoupper(fake()->randomLetter()),
            'floor_count' => fake()->numberBetween(1, 20),
            'status' => 'active',
        ];
    }
}
