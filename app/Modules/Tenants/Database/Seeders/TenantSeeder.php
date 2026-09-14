<?php

namespace App\Modules\Tenants\Database\Seeders;

use App\Modules\Companies\Models\Company;
use App\Modules\Tenants\Models\Tenant;
use Illuminate\Database\Seeder;

/**
 * No linkage to Properties/Units yet - that's a Lease (Module 16)
 * concern, not the Tenant record's own. Realistic sample data for
 * local dev: most tenants active, a few inactive/blacklisted so the
 * status filter/badge have something real to show.
 */
class TenantSeeder extends Seeder
{
    public function run(): void
    {
        Company::query()->each(function (Company $company): void {
            Tenant::factory()->count(random_int(4, 8))->create(['company_id' => $company->id]);
            Tenant::factory()->inactive()->count(random_int(0, 2))->create(['company_id' => $company->id]);
            Tenant::factory()->blacklisted()->count(random_int(0, 1))->create(['company_id' => $company->id]);
        });
    }
}
