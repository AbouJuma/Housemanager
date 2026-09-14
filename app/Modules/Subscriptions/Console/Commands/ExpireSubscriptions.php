<?php

namespace App\Modules\Subscriptions\Console\Commands;

use App\Modules\Companies\Models\Company;
use App\Modules\Subscriptions\Models\Subscription;
use Illuminate\Console\Command;

/**
 * Scheduled daily (see SubscriptionsServiceProvider::boot() - the same
 * precedent as Invoices' MarkOverdueInvoices, ARCHITECTURE.md §22).
 *
 * Two distinct outcomes, deliberately not the same severity:
 * - A trial whose period has ended without converting to a paid plan
 *   suspends the company outright (EnsureCompanyIsActive then blocks
 *   access) - a trial that never converts has no standing to keep using
 *   the app.
 * - A paid subscription whose period has ended without renewal is only
 *   flagged `past_due`, not suspended automatically - real billing
 *   failures deserve a grace period and a human decision, not an
 *   instant lockout the moment a card fails or an invoice goes unpaid.
 *   Suspending a past_due company is a deliberate SaaS-owner action
 *   (CompanySubscriptionController has no such automatic path).
 */
class ExpireSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire';

    protected $description = 'Suspend companies whose trial has ended without converting, and flag lapsed paid subscriptions as past due';

    public function handle(): int
    {
        // Only ever act on each company's CURRENT subscription (the
        // most recently created row - renewals/plan changes always
        // insert a new row rather than mutate one, see Subscription's
        // own class doc), never a superseded historical one whose
        // period also happens to have lapsed long ago.
        $currentSubscriptionIds = Subscription::query()->current();

        $expiredTrialCompanyIds = (clone $currentSubscriptionIds)
            ->where('status', 'trialing')
            ->whereDate('current_period_end', '<', now()->toDateString())
            ->pluck('company_id');

        $suspended = Company::query()
            ->whereIn('id', $expiredTrialCompanyIds)
            ->where('status', 'trial')
            ->update(['status' => 'suspended']);

        $pastDue = (clone $currentSubscriptionIds)
            ->where('status', 'active')
            ->whereDate('current_period_end', '<', now()->toDateString())
            ->update(['status' => 'past_due']);

        $this->info("Suspended {$suspended} expired trial(s); flagged {$pastDue} lapsed subscription(s) as past due.");

        return self::SUCCESS;
    }
}
