<?php

namespace App\Modules\Vendors\Database\Factories;

use App\Modules\Vendors\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vendor>
 */
class VendorFactory extends Factory
{
    protected $model = Vendor::class;

    public function definition(): array
    {
        $type = fake()->randomElement(['individual', 'company', 'company']);

        return [
            'name' => $type === 'individual' ? fake()->name() : fake()->company(),
            'type' => $type,
            'category' => fake()->randomElement([
                'plumbing', 'electrical', 'hvac', 'cleaning', 'landscaping',
                'pest_control', 'general_contractor', 'utility_provider', 'security', 'other',
            ]),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'city' => fake()->city(),
            'country_code' => fake()->countryCode(),
            'tax_id' => fake()->numerify('TIN-########'),
            'status' => 'active',
        ];
    }

    public function withBankDetails(): static
    {
        return $this->state(fn () => [
            'bank_details' => [
                'name' => fake()->randomElement(['CRDB Bank', 'NMB Bank', 'NBC Bank', 'Equity Bank']),
                'account_name' => fake()->name(),
                'account_number' => fake()->numerify('##########'),
                'branch' => fake()->city(),
                'swift_code' => strtoupper(fake()->bothify('????TZTZ')),
            ],
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => 'inactive']);
    }
}
