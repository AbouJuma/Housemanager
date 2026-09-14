<?php

namespace Tests\Feature\Subscriptions;

use App\Models\User;
use App\Modules\Companies\Models\Company;
use App\Modules\Subscriptions\Models\SubscriptionPlan;
use App\Modules\Subscriptions\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_saas_owner_can_put_a_company_on_a_plan(): void
    {
        $owner = User::factory()->create();
        $company = Company::factory()->create(['status' => 'trial']);
        $plan = SubscriptionPlan::factory()->create(['is_active' => true]);

        $this->actingAs($owner)->post(route('companies.subscription.change-plan', $company), [
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
        ])->assertRedirect(route('companies.show', $company));

        $this->assertSame($plan->id, $company->currentSubscription()->subscription_plan_id);
        $this->assertSame('active', $company->fresh()->status);
    }

    public function test_a_company_user_cannot_change_their_own_plan(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $plan = SubscriptionPlan::factory()->create(['is_active' => true]);

        $this->actingAs($user)->post(route('companies.subscription.change-plan', $company), [
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
        ])->assertForbidden();
    }

    public function test_a_companys_own_admin_cannot_reactivate_it(): void
    {
        $company = Company::factory()->create(['status' => 'suspended']);
        $plan = SubscriptionPlan::factory()->create();
        app(SubscriptionService::class)->subscribe($company, $plan, 'active');
        $company->update(['status' => 'suspended']);
        $admin = User::factory()->create(['company_id' => $company->id]);

        $this->actingAs($admin)->post(route('companies.subscription.reactivate', $company))->assertForbidden();
        $this->assertSame('suspended', $company->fresh()->status);
    }

    public function test_the_saas_owner_can_reactivate_a_suspended_company(): void
    {
        // Created up front, before any request runs - User::factory()
        // stamps company_id from the currently-set CompanyContext (via
        // BelongsToCompany, same as every tenant-owned model), and that
        // singleton is only ever reset by IdentifyCompany at the start
        // of an HTTP request. Creating the owner after an earlier
        // request in this same test process would otherwise pick up
        // that prior request's leftover (non-null) company context.
        $owner = User::factory()->create();

        $company = Company::factory()->create(['status' => 'suspended']);
        $plan = SubscriptionPlan::factory()->create();
        app(SubscriptionService::class)->subscribe($company, $plan, 'active');
        $company->update(['status' => 'suspended']);

        $this->actingAs($owner)->post(route('companies.subscription.reactivate', $company))
            ->assertRedirect(route('companies.show', $company));
        $this->assertSame('active', $company->fresh()->status);
    }

    public function test_the_saas_owner_can_extend_a_trial(): void
    {
        $owner = User::factory()->create();
        $company = Company::factory()->create();
        $plan = SubscriptionPlan::factory()->create();
        $subscription = app(SubscriptionService::class)->subscribe($company, $plan, 'trialing');
        $originalEnd = $subscription->current_period_end;

        $this->actingAs($owner)->post(route('subscriptions.extend-trial', $subscription), ['days' => 10])
            ->assertRedirect(route('companies.show', $company));

        $this->assertTrue($subscription->fresh()->current_period_end->equalTo($originalEnd->copy()->addDays(10)));
    }

    public function test_the_saas_owner_can_cancel_a_subscription(): void
    {
        $owner = User::factory()->create();
        $company = Company::factory()->create();
        $plan = SubscriptionPlan::factory()->create();
        $subscription = app(SubscriptionService::class)->subscribe($company, $plan, 'active');

        $this->actingAs($owner)->post(route('subscriptions.cancel', $subscription))
            ->assertRedirect(route('companies.show', $company));

        $this->assertSame('canceled', $subscription->fresh()->status);
        $this->assertSame('cancelled', $company->fresh()->status);
    }

    public function test_the_saas_owner_can_generate_mark_paid_and_void_an_invoice(): void
    {
        $owner = User::factory()->create();
        $company = Company::factory()->create();
        $plan = SubscriptionPlan::factory()->create(['price' => 79]);
        $subscription = app(SubscriptionService::class)->subscribe($company, $plan, 'active');

        $this->actingAs($owner)->post(route('subscriptions.invoices.generate', $subscription))
            ->assertRedirect(route('companies.show', $company));

        $invoice = $company->subscriptionInvoices()->latest('issue_date')->first();
        $this->assertNotNull($invoice);
        $this->assertSame('unpaid', $invoice->status);

        $this->actingAs($owner)->post(route('subscription-invoices.mark-paid', $invoice))
            ->assertRedirect(route('companies.show', $company));
        $this->assertSame('paid', $invoice->fresh()->status);

        $anotherInvoice = app(\App\Modules\Subscriptions\Services\SubscriptionInvoiceService::class)->generate($subscription);
        $this->actingAs($owner)->post(route('subscription-invoices.void', $anotherInvoice))
            ->assertRedirect(route('companies.show', $company));
        $this->assertSame('void', $anotherInvoice->fresh()->status);
    }

    public function test_a_company_user_cannot_manage_invoices(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $plan = SubscriptionPlan::factory()->create();
        $subscription = app(SubscriptionService::class)->subscribe($company, $plan, 'active');

        $this->actingAs($user)->post(route('subscriptions.invoices.generate', $subscription))->assertForbidden();
    }

    public function test_the_companys_show_page_renders_the_subscription_card_for_the_saas_owner(): void
    {
        $owner = User::factory()->create();
        $company = Company::factory()->create();
        $plan = SubscriptionPlan::factory()->create(['name' => 'Growth Plan']);
        app(SubscriptionService::class)->subscribe($company, $plan, 'active');

        $this->actingAs($owner)->get(route('companies.show', $company))
            ->assertOk()
            ->assertSee('Growth Plan');
    }

    public function test_the_companys_show_page_omits_the_subscription_card_for_the_companys_own_admin(): void
    {
        $company = Company::factory()->create();
        $plan = SubscriptionPlan::factory()->create(['name' => 'Growth Plan']);
        app(SubscriptionService::class)->subscribe($company, $plan, 'active');
        $admin = User::factory()->create(['company_id' => $company->id]);

        $this->actingAs($admin)->get(route('companies.show', $company))
            ->assertOk()
            ->assertDontSee('Growth Plan');
    }
}
