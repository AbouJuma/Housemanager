<?php

namespace App\Modules\LandlordPortal\Database\Seeders;

use App\Modules\Companies\Models\Company;
use App\Modules\Landlords\Models\Landlord;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Same shape as App\Modules\TenantPortal\Database\Seeders\
 * TenantPortalSeeder (Module 26) - enables one landlord per company with
 * a known fixed password, not LandlordPortalService::enableAccess()'s
 * own random one, so local dev has a ready-made login it can type.
 */
class LandlordPortalSeeder extends Seeder
{
    public function run(): void
    {
        Company::query()->each(function (Company $company): void {
            $landlord = Landlord::query()
                ->where('company_id', $company->id)
                ->whereNotNull('email')
                ->whereHas('properties')
                ->first();

            if (! $landlord) {
                return;
            }

            $landlord->forceFill(['password' => Hash::make('password')])->save();
        });
    }
}
