<?php

namespace App\Modules\Subscriptions\Database\Factories;

use App\Modules\Subscriptions\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SubscriptionPlan>
 */
class SubscriptionPlanFactory extends Factory
{
    protected $model = SubscriptionPlan::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true).' Plan';

        return [
            'name' => ucwords($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1000, 9999),
            'description' => fake()->sentence(),
            'price' => fake()->randomElement([0, 29, 79, 199]),
            'billing_cycle' => fake()->randomElement(['monthly', 'yearly']),
            'max_properties' => fake()->randomElement([5, 25, null]),
            'max_units' => fake()->randomElement([50, 250, null]),
            'max_users' => fake()->randomElement([3, 15, null]),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
