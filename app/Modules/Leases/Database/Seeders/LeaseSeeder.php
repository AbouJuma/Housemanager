<?php

namespace App\Modules\Leases\Database\Seeders;

use App\Modules\Companies\Models\Company;
use App\Modules\Leases\Models\Lease;
use App\Modules\Properties\Models\Unit;
use App\Modules\Tenants\Models\Tenant;
use Illuminate\Database\Seeder;

/**
 * Pairs a company's vacant units with its non-blacklisted tenants -
 * mirrors LandlordSeeder's "leave some unassigned" restraint (a
 * portfolio at less than full occupancy is the normal state, not an
 * error). Bypasses LeaseService and sets units.status directly, the
 * same direct-mutation convention LandlordSeeder uses for
 * properties.landlord_id - seeders talk to Eloquent directly rather
 * than simulating the HTTP-request-driven service layer.
 */
class LeaseSeeder extends Seeder
{
    public function run(): void
    {
        Company::query()->each(function (Company $company): void {
            $tenants = Tenant::query()->where('company_id', $company->id)->where('status', '!=', 'blacklisted')->get();
            $vacantUnits = Unit::query()->where('company_id', $company->id)->where('status', 'vacant')->get();

            if ($tenants->isEmpty() || $vacantUnits->isEmpty()) {
                return;
            }

            $activeUnits = $vacantUnits->random(min($vacantUnits->count(), max(1, (int) floor($vacantUnits->count() * 0.5))));

            foreach ($activeUnits as $unit) {
                Lease::factory()->create([
                    'company_id' => $company->id,
                    'tenant_id' => $tenants->random()->id,
                    'unit_id' => $unit->id,
                    'rent_amount' => $unit->rent_amount,
                ]);

                $unit->update(['status' => 'occupied']);
            }

            // A couple of historical leases (already expired/terminated)
            // on units that stay vacant post-seeding, so the leases list
            // has realistic non-active rows too, not just current ones.
            $remainingVacant = $vacantUnits->diff($activeUnits);

            if ($remainingVacant->isEmpty()) {
                return;
            }

            foreach ($remainingVacant->random(min($remainingVacant->count(), 2))->values() as $index => $unit) {
                Lease::factory()
                    ->{$index === 0 ? 'expired' : 'terminated'}()
                    ->create([
                        'company_id' => $company->id,
                        'tenant_id' => $tenants->random()->id,
                        'unit_id' => $unit->id,
                        'rent_amount' => $unit->rent_amount,
                    ]);
            }
        });
    }
}
