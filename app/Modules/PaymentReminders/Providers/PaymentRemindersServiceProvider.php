<?php

namespace App\Modules\PaymentReminders\Providers;

use App\Core\Foundation\ModuleServiceProvider;
use App\Modules\Companies\Events\CompanyCreated;
use App\Modules\PaymentReminders\Console\Commands\BackfillDefaultReminderRules;
use App\Modules\PaymentReminders\Listeners\SeedDefaultReminderRulesForCompany;
use Illuminate\Support\Facades\Event;

/**
 * No Gate::policy() registration - reminder rules are authorized by a
 * single flat `reminder-rules.manage` permission checked directly in
 * the controller (Spatie Permission's Gate::before hook makes any
 * registered permission name checkable via authorize() with no Policy
 * class needed - the same pattern App\Modules\Reports\Http\Controllers\
 * ReportController uses for `reports.view`), not a Policy tied to a
 * model.
 */
class PaymentRemindersServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        parent::boot();

        $this->commands([BackfillDefaultReminderRules::class]);

        Event::listen(CompanyCreated::class, SeedDefaultReminderRulesForCompany::class);
    }
}
