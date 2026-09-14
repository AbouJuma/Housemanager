<?php

namespace App\Modules\Subscriptions\Database\Factories;

use App\Modules\Companies\Models\Company;
use App\Modules\Subscriptions\Models\Subscription;
use App\Modules\Subscriptions\Models\SubscriptionInvoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubscriptionInvoice>
 */
class SubscriptionInvoiceFactory extends Factory
{
    protected $model = SubscriptionInvoice::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'subscription_id' => Subscription::factory(),
            'invoice_number' => 'SUB-'.fake()->unique()->numberBetween(100000, 999999),
            'amount' => fake()->randomElement([29, 79, 199]),
            'status' => 'unpaid',
            'issue_date' => now()->startOfDay(),
            'due_date' => now()->addDays(14)->startOfDay(),
            'paid_at' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => ['status' => 'paid', 'paid_at' => now()]);
    }

    public function void(): static
    {
        return $this->state(fn () => ['status' => 'void']);
    }

    public function overdue(): static
    {
        return $this->state(fn () => [
            'status' => 'unpaid',
            'issue_date' => now()->subDays(30)->startOfDay(),
            'due_date' => now()->subDays(16)->startOfDay(),
        ]);
    }
}
