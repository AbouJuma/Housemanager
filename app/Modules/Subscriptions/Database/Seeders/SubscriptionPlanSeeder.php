<?php

namespace App\Modules\Subscriptions\Database\Seeders;

use App\Modules\Subscriptions\Services\SubscriptionPlanService;
use Illuminate\Database\Seeder;

/**
 * Must run before CompanySeeder: SeedDefaultTrialSubscription (fired by
 * every company's creation) picks the cheapest active plan for the new
 * company's trial, so at least one plan needs to exist first (it fails
 * soft and silently skips otherwise, per the listener's own doc block).
 */
class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(SubscriptionPlanService::class);

        $service->create([
            'name' => 'Starter',
            'description' => 'For small portfolios just getting started.',
            'price' => 0,
            'billing_cycle' => 'monthly',
            'max_properties' => 5,
            'max_units' => 25,
            'max_users' => 3,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $service->create([
            'name' => 'Growth',
            'description' => 'For growing property management companies.',
            'price' => 79,
            'billing_cycle' => 'monthly',
            'max_properties' => 25,
            'max_units' => 250,
            'max_users' => 15,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $service->create([
            'name' => 'Enterprise',
            'description' => 'For large portfolios with unlimited scale.',
            'price' => 249,
            'billing_cycle' => 'monthly',
            'max_properties' => null,
            'max_units' => null,
            'max_users' => null,
            'is_active' => true,
            'sort_order' => 3,
        ]);
    }
}
