<?php

namespace App\Modules\Subscriptions\Database\Seeders;

use App\Modules\Companies\Models\Company;
use App\Modules\Subscriptions\Models\Subscription;
use App\Modules\Subscriptions\Models\SubscriptionPlan;
use App\Modules\Subscriptions\Services\SubscriptionService;
use Illuminate\Database\Seeder;

/**
 * A backfill, not part of the normal DatabaseSeeder::run() sequence -
 * the same "re-run standalone after deploying a module built later"
 * role ChartOfAccountsSeeder plays for Module 19 (ARCHITECTURE.md §22).
 * Every company created after this module shipped already gets a
 * trialing subscription automatically via SeedDefaultTrialSubscription
 * (fired on Companies\Events\CompanyCreated). This backfills companies
 * that existed before this module did, using their own pre-existing
 * `status`/`trial_ends_at` to decide which subscription state to seed -
 * a company already `active` gets an active subscription on the
 * cheapest plan, not a fresh trial it never actually had.
 */
class SubscriptionBackfillSeeder extends Seeder
{
    public function run(): void
    {
        $plan = SubscriptionPlan::query()->where('is_active', true)->orderBy('price')->first();

        if (! $plan) {
            $this->command?->warn('No active subscription plan exists - skipping subscription backfill.');

            return;
        }

        $service = app(SubscriptionService::class);
        $companyIdsWithSubscriptions = Subscription::query()->distinct()->pluck('company_id');

        Company::query()
            ->whereNotIn('id', $companyIdsWithSubscriptions)
            ->each(function (Company $company) use ($service, $plan): void {
                $status = $company->status === 'trial' ? 'trialing' : 'active';
                $service->subscribe($company, $plan, $status);
            });
    }
}
