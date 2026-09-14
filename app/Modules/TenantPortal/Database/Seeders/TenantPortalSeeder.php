<?php

namespace App\Modules\TenantPortal\Database\Seeders;

use App\Modules\Companies\Models\Company;
use App\Modules\Tenants\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Enables portal access for one tenant per company with a known fixed
 * password (not TenantPortalService::enableAccess()'s own random one -
 * local dev needs a login it can actually type), so `php artisan
 * db:seed` always leaves a ready "log in to the tenant portal" account
 * behind, the same way UserSeeder does for the staff side.
 */
class TenantPortalSeeder extends Seeder
{
    public function run(): void
    {
        Company::query()->each(function (Company $company): void {
            $tenant = Tenant::query()
                ->where('company_id', $company->id)
                ->whereNotNull('email')
                ->whereHas('leases', fn ($query) => $query->where('status', 'active'))
                ->first();

            if (! $tenant) {
                return;
            }

            $tenant->forceFill(['password' => Hash::make('password')])->save();
        });
    }
}
