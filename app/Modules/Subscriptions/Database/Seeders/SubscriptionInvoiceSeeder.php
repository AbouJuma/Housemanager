<?php

namespace App\Modules\Subscriptions\Database\Seeders;

use App\Modules\Companies\Models\Company;
use App\Modules\Subscriptions\Models\SubscriptionPlan;
use App\Modules\Subscriptions\Services\SubscriptionInvoiceService;
use App\Modules\Subscriptions\Services\SubscriptionService;
use Illuminate\Database\Seeder;

/**
 * Must run after CompanySeeder - SeedDefaultTrialSubscription (fired by
 * CompanyCreated) has already given every seeded company a subscription
 * by this point, so there's something here to bill against. Every
 * company starts on the cheapest (free) plan by default, so about half
 * are first upgraded to a paid plan here purely so there's realistic
 * billing history to look at - this mirrors a real SaaS owner manually
 * moving a company onto a paid tier via the "Change Plan" action.
 */
class SubscriptionInvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $subscriptionService = app(SubscriptionService::class);
        $invoiceService = app(SubscriptionInvoiceService::class);

        $paidPlans = SubscriptionPlan::query()->where('is_active', true)->where('price', '>', 0)->get();

        if ($paidPlans->isEmpty()) {
            $this->command?->warn('No paid subscription plan exists - skipping subscription invoice seeding.');

            return;
        }

        Company::query()->each(function (Company $company) use ($subscriptionService, $invoiceService, $paidPlans): void {
            $subscription = $company->currentSubscription();

            if ($subscription === null) {
                return;
            }

            if ((float) $subscription->plan->price === 0.0 && fake()->boolean(50)) {
                $subscription = $subscriptionService->changePlan($company, $paidPlans->random());
            }

            if ((float) $subscription->plan->price === 0.0) {
                return;
            }

            $invoice = $invoiceService->generate($subscription);

            $outcome = fake()->randomElement(['unpaid', 'paid', 'void']);

            if ($outcome === 'paid') {
                $invoiceService->markPaid($invoice);
            } elseif ($outcome === 'void') {
                $invoiceService->void($invoice);
            }
        });
    }
}
