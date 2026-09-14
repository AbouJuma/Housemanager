<?php

namespace App\Modules\Tenants\Database\Factories;

use App\Modules\Tenants\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $type = fake()->randomElement(['individual', 'individual', 'individual', 'company']);

        return [
            'name' => $type === 'individual' ? fake()->name() : fake()->company(),
            'type' => $type,
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'date_of_birth' => $type === 'individual' ? fake()->dateTimeBetween('-60 years', '-18 years') : null,
            'identification_type' => fake()->randomElement(['national_id', 'passport', 'driving_license']),
            'identification_number' => fake()->numerify('ID-########'),
            'city' => fake()->city(),
            'country_code' => fake()->countryCode(),
            'emergency_contact_name' => fake()->name(),
            'emergency_contact_phone' => fake()->phoneNumber(),
            'status' => 'active',
        ];
    }

    public function blacklisted(): static
    {
        return $this->state(fn () => ['status' => 'blacklisted']);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => 'inactive']);
    }
}
