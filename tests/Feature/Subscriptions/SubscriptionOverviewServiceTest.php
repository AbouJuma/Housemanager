<?php

namespace Tests\Feature\Subscriptions;

use App\Modules\Companies\Models\Company;
use App\Modules\Subscriptions\Models\SubscriptionPlan;
use App\Modules\Subscriptions\Services\SubscriptionInvoiceService;
use App\Modules\Subscriptions\Services\SubscriptionOverviewService;
use App\Modules\Subscriptions\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionOverviewServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SubscriptionOverviewService $overview;

    protected SubscriptionService $subscriptions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->overview = app(SubscriptionOverviewService::class);
        $this->subscriptions = app(SubscriptionService::class);
    }

    public function test_it_counts_active_and_trialing_subscriptions(): void
    {
        $plan = SubscriptionPlan::factory()->create();
        $this->subscriptions->subscribe(Company::factory()->create(), $plan, 'active');
        $this->subscriptions->subscribe(Company::factory()->create(), $plan, 'active');
        $this->subscriptions->subscribe(Company::factory()->create(), $plan, 'trialing');

        $summary = $this->overview->summarize();

        $this->assertSame(2, $summary['activeSubscriptionsCount']);
        $this->assertSame(1, $summary['trialingSubscriptionsCount']);
    }

    public function test_a_superseded_row_never_double_counts_a_company(): void
    {
        $starter = SubscriptionPlan::factory()->create(['price' => 0]);
        $growth = SubscriptionPlan::factory()->create(['price' => 79]);
        $company = Company::factory()->create();

        $this->subscriptions->subscribe($company, $starter, 'trialing');
        $this->subscriptions->changePlan($company, $growth);

        $summary = $this->overview->summarize();

        // Only the current (upgraded) row counts - not the superseded trial.
        $this->assertSame(1, $summary['activeSubscriptionsCount']);
        $this->assertSame(0, $summary['trialingSubscriptionsCount']);
    }

    public function test_mrr_normalizes_yearly_plans_to_a_monthly_figure(): void
    {
        $monthly = SubscriptionPlan::factory()->create(['price' => 100, 'billing_cycle' => 'monthly']);
        $yearly = SubscriptionPlan::factory()->create(['price' => 1200, 'billing_cycle' => 'yearly']);

        $this->subscriptions->subscribe(Company::factory()->create(), $monthly, 'active');
        $this->subscriptions->subscribe(Company::factory()->create(), $yearly, 'active');

        $summary = $this->overview->summarize();

        $this->assertSame(200.0, $summary['mrr']);
    }

    public function test_mrr_excludes_trialing_past_due_and_canceled_subscriptions(): void
    {
        $plan = SubscriptionPlan::factory()->create(['price' => 100]);

        $this->subscriptions->subscribe(Company::factory()->create(), $plan, 'trialing');
        $pastDueCompany = Company::factory()->create();
        $pastDue = $this->subscriptions->subscribe($pastDueCompany, $plan, 'active');
        $pastDue->update(['status' => 'past_due']);
        $canceledCompany = Company::factory()->create();
        $this->subscriptions->cancel($this->subscriptions->subscribe($canceledCompany, $plan, 'active'));

        $summary = $this->overview->summarize();

        $this->assertSame(0.0, $summary['mrr']);
    }

    public function test_subscribers_by_plan_counts_only_current_trialing_or_active_rows(): void
    {
        $plan = SubscriptionPlan::factory()->create();
        $this->subscriptions->subscribe(Company::factory()->create(), $plan, 'active');
        $this->subscriptions->subscribe(Company::factory()->create(), $plan, 'trialing');
        $canceled = $this->subscriptions->subscribe(Company::factory()->create(), $plan, 'active');
        $this->subscriptions->cancel($canceled);

        $summary = $this->overview->summarize();

        $this->assertSame(2, $summary['subscribersByPlan'][$plan->id]);
    }

    public function test_expiring_soon_includes_only_trialing_or_active_rows_within_the_window(): void
    {
        $plan = SubscriptionPlan::factory()->create();

        $soon = $this->subscriptions->subscribe(Company::factory()->create(), $plan, 'trialing', 5);
        $far = $this->subscriptions->subscribe(Company::factory()->create(), $plan, 'active');
        $far->update(['current_period_end' => now()->addDays(60)]);
        $canceled = $this->subscriptions->subscribe(Company::factory()->create(), $plan, 'active');
        $canceled->update(['current_period_end' => now()->addDays(3)]);
        $this->subscriptions->cancel($canceled);

        $summary = $this->overview->summarize();

        $this->assertSame(1, $summary['expiringSoonCount']);
        $this->assertSame($soon->id, $summary['expiringSoon']->first()->id);
    }

    public function test_unpaid_and_overdue_invoice_totals(): void
    {
        $plan = SubscriptionPlan::factory()->create(['price' => 50]);
        $subscription = $this->subscriptions->subscribe(Company::factory()->create(), $plan, 'active');
        $invoiceService = app(SubscriptionInvoiceService::class);

        $unpaidNotYetDue = $invoiceService->generate($subscription);

        $overdue = $invoiceService->generate($subscription);
        $overdue->update(['due_date' => now()->subDays(5)]);

        $paid = $invoiceService->generate($subscription);
        $invoiceService->markPaid($paid);

        $summary = $this->overview->summarize();

        $this->assertSame(2, $summary['unpaidInvoicesCount']);
        $this->assertSame(100.0, $summary['unpaidInvoicesTotal']);
        $this->assertSame(1, $summary['overdueInvoicesCount']);
        $this->assertSame(50.0, $summary['overdueInvoicesTotal']);
    }
}
