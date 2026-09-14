<?php

namespace App\Modules\Accounting\Database\Factories;

use App\Modules\Accounting\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->numerify('9###'),
            'name' => fake()->randomElement(['Cash', 'Bank', 'Accounts Receivable', 'Rental Income', 'Maintenance Expense']),
            'type' => fake()->randomElement(['asset', 'liability', 'equity', 'income', 'expense']),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
