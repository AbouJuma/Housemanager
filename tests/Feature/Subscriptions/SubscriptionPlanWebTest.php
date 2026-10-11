<?php

namespace Tests\Feature\Subscriptions;

use App\Models\User;
use App\Modules\Companies\Models\Company;
use App\Modules\Subscriptions\Models\SubscriptionPlan;
use App\Modules\Subscriptions\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionPlanWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_company_user_cannot_view_the_plan_catalog(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user)->get(route('subscription-plans.index'))->assertForbidden();
    }

    public function test_the_saas_owner_can_view_the_plan_catalog(): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner)->get(route('subscription-plans.index'))->assertOk();
    }

    public function test_the_saas_owner_can_create_a_plan(): void
    {
        $owner = User::factory()->create();

        $response = $this->actingAs($owner)->post(route('subscription-plans.store'), [
            'name' => 'Growth',
            'price' => 79,
            'billing_cycle' => 'monthly',
            'max_properties' => 25,
            'max_units' => 250,
            'max_users' => 15,
            'is_active' => 1,
        ]);

        $plan = SubscriptionPlan::firstWhere('name', 'Growth');
        $response->assertRedirect(route('subscription-plans.show', $plan));
        $this->assertNotNull($plan);
        $this->assertSame('growth', $plan->slug);
    }

    public function test_a_company_user_cannot_create_a_plan(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user)->post(route('subscription-plans.store'), [
            'name' => 'Growth',
            'price' => 79,
            'billing_cycle' => 'monthly',
        ])->assertForbidden();

        $this->assertDatabaseMissing('subscription_plans', ['name' => 'Growth']);
    }

    public function test_the_saas_owner_can_update_a_plan(): void
    {
        $owner = User::factory()->create();
        $plan = SubscriptionPlan::factory()->create(['name' => 'Old Name']);

        $this->actingAs($owner)->put(route('subscription-plans.update', $plan), [
            'name' => 'New Name',
            'price' => $plan->price,
            'billing_cycle' => $plan->billing_cycle,
        ])->assertRedirect(route('subscription-plans.show', $plan));

        $this->assertSame('New Name', $plan->fresh()->name);
    }

    public function test_the_saas_owner_can_delete_a_plan(): void
    {
        $owner = User::factory()->create();
        $plan = SubscriptionPlan::factory()->create();

        $this->actingAs($owner)->delete(route('subscription-plans.destroy', $plan))
            ->assertRedirect(route('subscription-plans.index'));

        $this->assertSoftDeleted($plan);
    }

    public function test_the_catalog_page_shows_the_overview_kpis_and_subscriber_counts(): void
    {
        $owner = User::factory()->create();
        $plan = SubscriptionPlan::factory()->create(['name' => 'Growth Plan', 'price' => 79, 'billing_cycle' => 'monthly']);
        $company = Company::factory()->create();
        app(SubscriptionService::class)->subscribe($company, $plan, 'active');

        $response = $this->actingAs($owner)->get(route('subscription-plans.index'));

        $response->assertOk();
        $response->assertViewHas('overview', function (array $overview) {
            return $overview['activeSubscriptionsCount'] === 1 && $overview['mrr'] === 79.0;
        });
        $response->assertSee('Growth Plan');
        $response->assertSee('Active Subscriptions');
        $response->assertSee('MRR');
    }

    public function test_the_catalog_page_lists_a_subscription_expiring_soon(): void
    {
        $owner = User::factory()->create();
        $plan = SubscriptionPlan::factory()->create();
        $company = Company::factory()->create(['name' => 'Expiring Co']);
        app(SubscriptionService::class)->subscribe($company, $plan, 'trialing', 3);

        $this->actingAs($owner)->get(route('subscription-plans.index'))
            ->assertOk()
            ->assertSee('Expiring Soon')
            ->assertSee('Expiring Co');
    }
}
