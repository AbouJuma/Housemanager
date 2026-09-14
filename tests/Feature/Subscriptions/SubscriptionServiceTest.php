<?php

namespace Tests\Feature\Subscriptions;

use App\Modules\Companies\Models\Company;
use App\Modules\Subscriptions\Models\SubscriptionPlan;
use App\Modules\Subscriptions\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SubscriptionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SubscriptionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(SubscriptionService::class);
    }

    public function test_subscribing_a_company_to_a_trial_sets_company_status_to_trial(): void
    {
        $company = Company::factory()->create(['status' => 'active']);
        $plan = SubscriptionPlan::factory()->create();

        $subscription = $this->service->subscribe($company, $plan, 'trialing');

        $this->assertSame('trialing', $subscription->status);
        $this->assertSame('trial', $company->fresh()->status);
        $this->assertNotNull($company->fresh()->trial_ends_at);
    }

    public function test_subscribing_a_company_as_active_sets_company_status_to_active(): void
    {
        $company = Company::factory()->create(['status' => 'trial']);
        $plan = SubscriptionPlan::factory()->create();

        $subscription = $this->service->subscribe($company, $plan, 'active');

        $this->assertSame('active', $subscription->status);
        $this->assertSame('active', $company->fresh()->status);
        $this->assertNull($company->fresh()->trial_ends_at);
    }

    public function test_changing_plan_creates_a_new_row_and_leaves_the_old_one_untouched(): void
    {
        $company = Company::factory()->create();
        $starter = SubscriptionPlan::factory()->create(['price' => 0]);
        $growth = SubscriptionPlan::factory()->create(['price' => 79]);

        $original = $this->service->subscribe($company, $starter, 'trialing');
        $upgraded = $this->service->changePlan($company, $growth);

        $this->assertSame(2, $company->subscriptions()->count());
        $this->assertSame('trialing', $original->fresh()->status);
        $this->assertSame($starter->id, $original->fresh()->subscription_plan_id);
        $this->assertSame('active', $upgraded->status);
        $this->assertSame($growth->id, $upgraded->subscription_plan_id);
        $this->assertSame('active', $company->fresh()->status);
    }

    public function test_current_subscription_resolves_to_the_most_recently_created_row_even_on_the_same_calendar_day(): void
    {
        $company = Company::factory()->create();
        $starter = SubscriptionPlan::factory()->create(['price' => 0]);
        $growth = SubscriptionPlan::factory()->create(['price' => 79]);

        $this->service->subscribe($company, $starter, 'trialing');
        $upgraded = $this->service->changePlan($company, $growth);

        // Both rows share the same `current_period_start` date (created
        // the same day in this test) - currentSubscription() must still
        // resolve to the row created second, not tie arbitrarily.
        $this->assertSame($upgraded->id, $company->currentSubscription()->id);
    }

    public function test_renewing_creates_a_new_row_starting_from_the_old_periods_end(): void
    {
        $company = Company::factory()->create();
        $plan = SubscriptionPlan::factory()->create(['billing_cycle' => 'monthly']);

        $subscription = $this->service->subscribe($company, $plan, 'active');
        $renewed = $this->service->renew($subscription);

        $this->assertNotSame($subscription->id, $renewed->id);
        $this->assertTrue($renewed->current_period_start->equalTo($subscription->current_period_end));
        $this->assertSame('active', $company->fresh()->status);
    }

    public function test_extending_a_trial_mutates_the_same_row_in_place(): void
    {
        $company = Company::factory()->create();
        $plan = SubscriptionPlan::factory()->create();
        $subscription = $this->service->subscribe($company, $plan, 'trialing');
        $originalEnd = $subscription->current_period_end;

        $extended = $this->service->extendTrial($subscription, 7);

        $this->assertSame($subscription->id, $extended->id);
        $this->assertTrue($extended->current_period_end->equalTo($originalEnd->copy()->addDays(7)));
        $this->assertTrue($company->fresh()->trial_ends_at->equalTo($extended->current_period_end));
    }

    public function test_extending_a_non_trialing_subscription_is_rejected(): void
    {
        $company = Company::factory()->create();
        $plan = SubscriptionPlan::factory()->create();
        $subscription = $this->service->subscribe($company, $plan, 'active');

        $this->expectException(ValidationException::class);

        $this->service->extendTrial($subscription, 7);
    }

    public function test_cancelling_marks_the_subscription_canceled_and_the_company_cancelled(): void
    {
        $company = Company::factory()->create();
        $plan = SubscriptionPlan::factory()->create();
        $subscription = $this->service->subscribe($company, $plan, 'active');

        $canceled = $this->service->cancel($subscription);

        $this->assertSame('canceled', $canceled->status);
        $this->assertNotNull($canceled->canceled_at);
        $this->assertSame('cancelled', $company->fresh()->status);
    }

    public function test_reactivating_restores_company_status_from_the_current_subscription(): void
    {
        $company = Company::factory()->create();
        $plan = SubscriptionPlan::factory()->create();
        $subscription = $this->service->subscribe($company, $plan, 'active');
        $company->update(['status' => 'suspended']);

        $this->service->reactivate($company);

        $this->assertSame('active', $company->fresh()->status);
    }

    public function test_reactivating_a_company_with_no_subscription_is_rejected(): void
    {
        $company = Company::factory()->create(['status' => 'suspended']);

        $this->expectException(ValidationException::class);

        $this->service->reactivate($company);
    }
}
