<?php

namespace App\Modules\Maintenance\Database\Factories;

use App\Modules\Maintenance\Models\MaintenanceRequest;
use App\Modules\Properties\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaintenanceRequest>
 */
class MaintenanceRequestFactory extends Factory
{
    protected $model = MaintenanceRequest::class;

    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'title' => fake()->randomElement([
                'Leaking kitchen faucet', 'Broken AC unit', 'Clogged drain', 'Power outlet not working',
                'Pest infestation', 'Overgrown landscaping', 'Broken window latch', 'Water heater not working',
            ]),
            'description' => fake()->sentence(),
            'category' => fake()->randomElement([
                'plumbing', 'electrical', 'hvac', 'cleaning', 'landscaping',
                'pest_control', 'general_contractor', 'utility_provider', 'security', 'other',
            ]),
            'priority' => fake()->randomElement(['low', 'medium', 'medium', 'high', 'urgent']),
            'status' => 'open',
            'reported_date' => now()->subDays(random_int(0, 30))->toDateString(),
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn () => ['status' => 'in_progress', 'scheduled_date' => now()->addDays(random_int(1, 7))->toDateString()]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => 'completed',
            'completed_date' => now()->subDays(random_int(0, 5))->toDateString(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => 'cancelled']);
    }
}
