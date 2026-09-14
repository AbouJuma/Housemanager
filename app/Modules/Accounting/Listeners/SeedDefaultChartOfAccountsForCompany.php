<?php

namespace App\Modules\Accounting\Listeners;

use App\Modules\Accounting\Models\Account;
use App\Modules\Companies\Events\CompanyCreated;

/**
 * Same trigger Module 3's SeedDefaultRolesForCompany uses. Unlike that
 * listener, no PermissionsTeam::runAs() wrapper is needed - Account is
 * a plain BelongsToCompany model, not a Spatie team-scoped one, so
 * company_id is just passed explicitly.
 */
class SeedDefaultChartOfAccountsForCompany
{
    public function handle(CompanyCreated $event): void
    {
        foreach (config('accounting.default_chart') as $account) {
            Account::create([
                'company_id' => $event->company->id,
                'code' => $account['code'],
                'name' => $account['name'],
                'type' => $account['type'],
            ]);
        }
    }
}
