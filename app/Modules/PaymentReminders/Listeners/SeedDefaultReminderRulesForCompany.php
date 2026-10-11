<?php

namespace App\Modules\PaymentReminders\Listeners;

use App\Modules\Companies\Events\CompanyCreated;
use App\Modules\PaymentReminders\Services\ReminderRuleService;

/**
 * Gives every new company the same reminder cadence the old hardcoded
 * commands used (3-day due-soon + weekly overdue for rent, 7-day
 * due-soon + monthly overdue for sale installments), pre-populated as
 * editable rows instead of literals in code - see
 * ReminderRuleService::defaultRuleDefinitions(). Existing companies
 * (created before this listener existed) are backfilled once via
 * `php artisan payment-reminders:backfill-default-rules`.
 */
class SeedDefaultReminderRulesForCompany
{
    public function __construct(protected ReminderRuleService $service)
    {
    }

    public function handle(CompanyCreated $event): void
    {
        $this->service->seedDefaultsFor($event->company);
    }
}
