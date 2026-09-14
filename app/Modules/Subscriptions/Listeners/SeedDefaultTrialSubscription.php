<?php

namespace App\Modules\Subscriptions\Listeners;

use App\Modules\Companies\Events\CompanyCreated;
use App\Modules\Subscriptions\Models\SubscriptionPlan;
use App\Modules\Subscriptions\Services\SubscriptionService;
use Illuminate\Support\Facades\Log;

/**
 * Same trigger as Module 3's SeedDefaultRolesForCompany and Module 19's
 * SeedDefaultChartOfAccountsForCompany - every new company starts on a
 * real trialing Subscription automatically, on whichever active plan is
 * cheapest (the realistic "new signups land on the Starter tier"
 * default). Fails soft, not hard, if no plan exists yet (e.g. a fresh
 * install before the SaaS owner has created any plans) - the same
 * "an automatic listener must never block the primary action it's
 * reacting to" discipline as Accounting's InvoiceSent listeners
 * (ARCHITECTURE.md §12): a company must always be creatable even if
 * billing isn't configured yet.
 */
class SeedDefaultTrialSubscription
{
    public function __construct(protected SubscriptionService $service)
    {
    }

    public function handle(CompanyCreated $event): void
    {
        $plan = SubscriptionPlan::query()->where('is_active', true)->orderBy('price')->first();

        if (! $plan) {
            Log::warning('No active subscription plan exists - skipped seeding a default trial subscription.', [
                'company_id' => $event->company->id,
            ]);

            return;
        }

        $this->service->subscribe($event->company, $plan, 'trialing');
    }
}
