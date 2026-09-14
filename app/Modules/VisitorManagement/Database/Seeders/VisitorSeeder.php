<?php

namespace App\Modules\VisitorManagement\Database\Seeders;

use App\Modules\Companies\Models\Company;
use App\Modules\Properties\Models\Property;
use App\Modules\Tenants\Models\Tenant;
use App\Modules\Vendors\Models\Vendor;
use App\Modules\VisitorManagement\Models\Visitor;
use Illuminate\Database\Seeder;

class VisitorSeeder extends Seeder
{
    public function run(): void
    {
        Company::query()->each(function (Company $company): void {
            $properties = Property::query()->where('company_id', $company->id)->get();
            if ($properties->isEmpty()) {
                return;
            }
            $tenants = Tenant::query()->where('company_id', $company->id)->where('status', '!=', 'blacklisted')->get();
            $vendors = Vendor::query()->where('company_id', $company->id)->where('status', 'active')->get();

            for ($i = 0; $i < random_int(5, 12); $i++) {
                $checkedOut = fake()->boolean(70);

                $factory = $checkedOut ? Visitor::factory()->checkedOut() : Visitor::factory();

                $factory->create([
                    'company_id' => $company->id,
                    'property_id' => $properties->random()->id,
                    'tenant_id' => $tenants->isNotEmpty() && fake()->boolean(50) ? $tenants->random()->id : null,
                    'vendor_id' => $vendors->isNotEmpty() && fake()->boolean(25) ? $vendors->random()->id : null,
                    'host_name' => $tenants->isEmpty() && fake()->boolean(40) ? fake()->randomElement(['Building Management', 'Front Desk', 'Landlord']) : null,
                ]);
            }
        });
    }
}
