<?php

namespace Tests\Feature\Subscriptions;

use App\Models\User;
use App\Modules\Companies\Models\Company;
use App\Modules\Subscriptions\Models\SubscriptionPlan;
use App\Modules\Subscriptions\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_company_user_can_view_their_own_billing_page(): void
    {
        $company = Company::factory()->create();
        $plan = SubscriptionPlan::factory()->create();
        app(SubscriptionService::class)->subscribe($company, $plan, 'active');
        $user = User::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user)->get(route('billing.index'))
            ->assertOk()
            ->assertSee($plan->name);
    }

    public function test_a_company_with_no_subscription_yet_still_sees_the_page(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user)->get(route('billing.index'))->assertOk();
    }

    public function test_the_saas_owner_cannot_view_the_billing_page(): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner)->get(route('billing.index'))->assertForbidden();
    }
}
