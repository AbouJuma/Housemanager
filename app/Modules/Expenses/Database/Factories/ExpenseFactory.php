<?php

namespace App\Modules\Expenses\Database\Factories;

use App\Modules\Accounting\Models\Account;
use App\Modules\Expenses\Models\Expense;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 *
 * Bare factory use (mostly tests) doesn't go through ExpenseService::create()
 * and so never posts a journal entry - tests that need one call the
 * service directly instead.
 */
class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'description' => fake()->randomElement(['Plumbing repair', 'Electrical repair', 'Water bill', 'Electricity bill', 'Cleaning service']),
            'vendor_name' => fake()->company(),
            'amount' => fake()->randomFloat(2, 20, 800),
            'expense_date' => now()->subDays(random_int(0, 60))->toDateString(),
            'payment_status' => 'unpaid',
            'status' => 'active',
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'payment_status' => 'paid',
            'payment_method' => fake()->randomElement(['cash', 'bank_transfer', 'mobile_money']),
            'paid_on' => now()->toDateString(),
        ]);
    }

    public function voided(): static
    {
        return $this->state(fn () => ['status' => 'voided']);
    }
}
