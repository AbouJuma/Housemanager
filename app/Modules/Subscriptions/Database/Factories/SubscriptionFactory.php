<?php

namespace App\Modules\Subscriptions\Database\Factories;

use App\Modules\Companies\Models\Company;
use App\Modules\Subscriptions\Models\Subscription;
use App\Modules\Subscriptions\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'subscription_plan_id' => SubscriptionPlan::factory(),
            'status' => 'trialing',
            'current_period_start' => now()->startOfDay(),
            'current_period_end' => now()->addDays(14)->startOfDay(),
            'canceled_at' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'status' => 'active',
            'current_period_start' => now()->startOfDay(),
            'current_period_end' => now()->addMonth()->startOfDay(),
        ]);
    }

    public function pastDue(): static
    {
        return $this->state(fn () => [
            'status' => 'past_due',
            'current_period_start' => now()->subMonth()->startOfDay(),
            'current_period_end' => now()->subDay()->startOfDay(),
        ]);
    }

    public function canceled(): static
    {
        return $this->state(fn () => [
            'status' => 'canceled',
            'canceled_at' => now(),
        ]);
    }
}
