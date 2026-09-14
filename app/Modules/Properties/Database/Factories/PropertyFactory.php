<?php

namespace App\Modules\Properties\Database\Factories;

use App\Modules\Properties\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Property>
 */
class PropertyFactory extends Factory
{
    protected $model = Property::class;

    public function definition(): array
    {
        return [
            'name' => fake()->streetName().' '.fake()->randomElement(['Apartments', 'Plaza', 'Court', 'Residences', 'Complex']),
            'type' => fake()->randomElement([
                'residential', 'commercial', 'mixed_use', 'warehouse',
                'retail', 'hotel', 'hostel', 'industrial', 'land', 'other',
            ]),
            'status' => 'active',
            'description' => fake()->optional()->paragraph(),
            'address_line1' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'postal_code' => fake()->postcode(),
            'country_code' => fake()->countryCode(),
            'total_area' => fake()->randomFloat(2, 100, 10000),
            'area_unit' => 'sqm',
        ];
    }
}
