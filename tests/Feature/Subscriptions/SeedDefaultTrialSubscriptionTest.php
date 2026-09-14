<?php

namespace Tests\Feature\Subscriptions;

use App\Modules\Companies\Events\CompanyCreated;
use App\Modules\Companies\Models\Company;
use App\Modules\Subscriptions\Models\Subscription;
use App\Modules\Subscriptions\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SeedDefaultTrialSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_company_is_put_on_a_trial_of_the_cheapest_active_plan(): void
    {
        SubscriptionPlan::factory()->create(['price' => 199, 'is_active' => true]);
        $cheapest = SubscriptionPlan::factory()->create(['price' => 29, 'is_active' => true]);
        SubscriptionPlan::factory()->create(['price' => 9, 'is_active' => false]);

        $company = Company::factory()->create(['status' => 'active']);

        CompanyCreated::dispatch($company);

        $subscription = $company->currentSubscription();

        $this->assertNotNull($subscription);
        $this->assertSame('trialing', $subscription->status);
        $this->assertSame($cheapest->id, $subscription->subscription_plan_id);
        $this->assertSame('trial', $company->fresh()->status);
    }

    public function test_it_fails_soft_when_no_active_plan_exists(): void
    {
        Log::shouldReceive('warning')->once();

        $company = Company::factory()->create();

        CompanyCreated::dispatch($company);

        $this->assertSame(0, Subscription::query()->where('company_id', $company->id)->count());
    }
}
