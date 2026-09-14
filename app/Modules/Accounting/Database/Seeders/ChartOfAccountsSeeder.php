<?php

namespace App\Modules\Accounting\Database\Seeders;

use App\Modules\Accounting\Models\Account;
use App\Modules\Companies\Models\Company;
use Illuminate\Database\Seeder;

/**
 * A backfill, not part of the normal DatabaseSeeder::run() sequence -
 * every company created after this module shipped already gets its
 * default chart automatically via SeedDefaultChartOfAccountsForCompany
 * (fired on Companies\Events\CompanyCreated, the same as
 * SeedDefaultRolesForCompany, ARCHITECTURE.md §12). This exists purely
 * to backfill companies that were seeded/created before this module
 * existed - the same "re-run after deploying a module built later"
 * role PermissionSeeder plays for permissions. Idempotent: skips any
 * company that already has at least one account.
 */
class ChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        // Company has no accounts() relation - it doesn't accumulate a
        // hasMany for every module that belongs to it (see e.g.
        // Lease's own note on why Company::leases() doesn't exist
        // either), so this checks directly instead.
        $companyIdsWithAccounts = Account::query()->distinct()->pluck('company_id');

        Company::query()
            ->whereNotIn('id', $companyIdsWithAccounts)
            ->each(function (Company $company): void {
                foreach (config('accounting.default_chart') as $account) {
                    Account::create([
                        'company_id' => $company->id,
                        'code' => $account['code'],
                        'name' => $account['name'],
                        'type' => $account['type'],
                    ]);
                }
            });
    }
}
