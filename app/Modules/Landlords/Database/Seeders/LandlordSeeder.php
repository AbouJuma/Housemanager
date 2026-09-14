<?php

namespace App\Modules\Landlords\Database\Seeders;

use App\Modules\Companies\Models\Company;
use App\Modules\Landlords\Models\Landlord;
use App\Modules\Properties\Models\Property;
use Illuminate\Database\Seeder;

class LandlordSeeder extends Seeder
{
    public function run(): void
    {
        Company::query()->each(function (Company $company): void {
            $landlords = Landlord::factory()
                ->count(random_int(2, 4))
                ->withBankDetails()
                ->create(['company_id' => $company->id]);

            if ($landlords->isEmpty()) {
                return;
            }

            // Assign a landlord to most (not all) of this company's
            // properties, leaving some unassigned - a property without
            // a landlord yet is a normal, real state, not an error.
            Property::query()
                ->where('company_id', $company->id)
                ->inRandomOrder()
                ->limit((int) floor(Property::query()->where('company_id', $company->id)->count() * 0.7))
                ->get()
                ->each(fn (Property $property) => $property->update(['landlord_id' => $landlords->random()->id]));
        });
    }
}
