<?php

namespace App\Modules\Properties\Database\Seeders;

use App\Modules\Companies\Models\Company;
use App\Modules\Properties\Models\Building;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use Illuminate\Database\Seeder;

class PropertySeeder extends Seeder
{
    public function run(): void
    {
        Company::query()->each(function (Company $company): void {
            Property::factory()
                ->count(random_int(1, 3))
                ->create(['company_id' => $company->id])
                ->each(function (Property $property): void {
                    Building::factory()
                        ->count(random_int(1, 2))
                        ->create(['company_id' => $property->company_id, 'property_id' => $property->id])
                        ->each(function (Building $building): void {
                            Unit::factory()
                                ->count(random_int(2, 6))
                                ->sequence(fn ($sequence) => ['unit_number' => 'U'.($sequence->index + 1)])
                                ->create(['company_id' => $building->company_id, 'building_id' => $building->id]);
                        });
                });
        });
    }
}
