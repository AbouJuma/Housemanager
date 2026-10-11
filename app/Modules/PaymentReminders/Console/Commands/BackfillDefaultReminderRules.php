<?php

namespace App\Modules\PaymentReminders\Console\Commands;

use App\Modules\Companies\Models\Company;
use App\Modules\PaymentReminders\Services\ReminderRuleService;
use Illuminate\Console\Command;

/**
 * One-time backfill for companies created before
 * SeedDefaultReminderRulesForCompany existed - gives them the same
 * default reminder rules a newly created company gets automatically.
 * Safe to run more than once: seedDefaultsFor() uses firstOrCreate, so
 * a company that already has its default rules is left untouched.
 */
class BackfillDefaultReminderRules extends Command
{
    protected $signature = 'payment-reminders:backfill-default-rules';

    protected $description = 'Seed default payment reminder rules for every company that does not already have them';

    public function handle(ReminderRuleService $service): int
    {
        $companies = Company::query()->get();

        $this->withProgressBar($companies, function (Company $company) use ($service): void {
            $service->seedDefaultsFor($company);
        });

        $this->newLine();
        $this->info("Backfilled default reminder rules for {$companies->count()} compan".($companies->count() === 1 ? 'y' : 'ies').'.');

        return self::SUCCESS;
    }
}
