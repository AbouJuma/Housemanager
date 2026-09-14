<?php

namespace App\Modules\Leases\Database\Factories;

use App\Modules\Leases\Models\Lease;
use App\Modules\Properties\Models\Unit;
use App\Modules\Tenants\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lease>
 */
class LeaseFactory extends Factory
{
    protected $model = Lease::class;

    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-6 months', '-1 month');

        return [
            'tenant_id' => Tenant::factory(),
            'unit_id' => Unit::factory(),
            'start_date' => $start,
            'end_date' => (clone $start)->modify('+1 year'),
            'billing_cycle' => 'monthly',
            'rent_amount' => fake()->randomFloat(2, 100, 5000),
            'deposit_amount' => fake()->randomFloat(2, 100, 5000),
            'status' => 'active',
        ];
    }

    public function expired(): static
    {
        return $this->state(function (array $attributes) {
            $start = fake()->dateTimeBetween('-2 years', '-13 months');

            return [
                'start_date' => $start,
                'end_date' => (clone $start)->modify('+1 year'),
                'status' => 'expired',
            ];
        });
    }

    public function terminated(): static
    {
        return $this->state(fn () => [
            'status' => 'terminated',
            'terminated_at' => fake()->dateTimeBetween('-6 months', '-1 week'),
            'termination_reason' => fake()->randomElement([
                'Tenant relocated for work.', 'Mutual agreement to end early.', 'Non-payment of rent.',
            ]),
        ]);
    }
}
