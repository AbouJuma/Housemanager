<?php

namespace App\Modules\Vendors\Database\Seeders;

use App\Modules\Companies\Models\Company;
use App\Modules\Vendors\Models\Vendor;
use Illuminate\Database\Seeder;

class VendorSeeder extends Seeder
{
    public function run(): void
    {
        Company::query()->each(function (Company $company): void {
            Vendor::factory()
                ->count(random_int(3, 6))
                ->withBankDetails()
                ->create(['company_id' => $company->id]);
        });
    }
}
