<?php

namespace Tests\Feature\Subscriptions;

use App\Modules\Companies\Models\Company;
use App\Modules\Subscriptions\Models\Subscription;
use App\Modules\Subscriptions\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpireSubscriptionsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_company_whose_trial_has_lapsed_is_suspended(): void
    {
        $company = Company::factory()->create(['status' => 'trial']);
        $plan = SubscriptionPlan::factory()->create();
        Subscription::factory()->create([
            'company_id' => $company->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'trialing',
            'current_period_start' => now()->subDays(20),
            'current_period_end' => now()->subDays(6),
        ]);

        $this->artisan('subscriptions:expire')->assertSuccessful();

        $this->assertSame('suspended', $company->fresh()->status);
    }

    public function test_a_company_whose_trial_is_still_running_is_untouched(): void
    {
        $company = Company::factory()->create(['status' => 'trial']);
        $plan = SubscriptionPlan::factory()->create();
        Subscription::factory()->create([
            'company_id' => $company->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'trialing',
            'current_period_start' => now(),
            'current_period_end' => now()->addDays(7),
        ]);

        $this->artisan('subscriptions:expire')->assertSuccessful();

        $this->assertSame('trial', $company->fresh()->status);
    }

    public function test_a_lapsed_active_subscription_is_flagged_past_due_not_suspended(): void
    {
        $company = Company::factory()->create(['status' => 'active']);
        $plan = SubscriptionPlan::factory()->create();
        $subscription = Subscription::factory()->create([
            'company_id' => $company->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'current_period_start' => now()->subMonth(),
            'current_period_end' => now()->subDay(),
        ]);

        $this->artisan('subscriptions:expire')->assertSuccessful();

        $this->assertSame('past_due', $subscription->fresh()->status);
        $this->assertSame('active', $company->fresh()->status, 'a past_due company is never auto-suspended');
    }

    /**
     * Regression test for the bug caught during this module's own build:
     * a superseded row (left behind by a plan change/renewal, per
     * Subscription's immutable-history design) must never be touched by
     * this command even though its own period has long since lapsed -
     * only each company's actual CURRENT subscription row is in scope.
     */
    public function test_a_superseded_historical_row_is_never_flagged_or_used_to_suspend(): void
    {
        $company = Company::factory()->create(['status' => 'active']);
        $plan = SubscriptionPlan::factory()->create();

        $old = Subscription::factory()->create([
            'company_id' => $company->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'trialing',
            'current_period_start' => now()->subYear(),
            'current_period_end' => now()->subYear()->addDays(14),
        ]);

        $current = Subscription::factory()->create([
            'company_id' => $company->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'current_period_start' => now()->subDays(5),
            'current_period_end' => now()->addDays(25),
        ]);

        $this->artisan('subscriptions:expire')->assertSuccessful();

        $this->assertSame('trialing', $old->fresh()->status);
        $this->assertSame('active', $current->fresh()->status);
        $this->assertSame('active', $company->fresh()->status);
    }
}
