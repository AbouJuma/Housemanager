<?php

namespace App\Modules\VisitorManagement\Database\Factories;

use App\Modules\Properties\Models\Property;
use App\Modules\VisitorManagement\Models\Visitor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Visitor>
 */
class VisitorFactory extends Factory
{
    protected $model = Visitor::class;

    public function definition(): array
    {
        $checkIn = now()->subHours(random_int(1, 72));

        return [
            'property_id' => Property::factory(),
            'visitor_name' => fake()->name(),
            'visitor_phone' => fake()->boolean(60) ? fake()->phoneNumber() : null,
            'purpose' => fake()->randomElement(['delivery', 'meeting', 'maintenance', 'interview', 'personal', 'other']),
            'vehicle_plate' => fake()->boolean(40) ? strtoupper(fake()->bothify('T###???')) : null,
            'check_in_at' => $checkIn,
        ];
    }

    public function checkedOut(): static
    {
        return $this->state(fn (array $attributes) => [
            'check_out_at' => $attributes['check_in_at']->copy()->addMinutes(random_int(15, 240)),
        ]);
    }
}
