<?php

namespace App\Modules\Companies\Database\Seeders;

use App\Modules\Companies\Events\CompanyCreated;
use App\Modules\Companies\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        // Factory::create() is direct Eloquent creation - it bypasses
        // CompanyService::create() and therefore never fires
        // CompanyCreated, so seeded companies need it dispatched
        // explicitly here to end up with the same default Admin/Staff
        // roles a company created through the real UI would get.
        collect([
            fn () => Company::factory()->count(10)->active()->create(),
            fn () => Company::factory()->count(3)->suspended()->create(),
            fn () => Company::factory()->count(5)->create(),
        ])
            ->flatMap(fn ($make) => $make())
            ->each(fn (Company $company) => CompanyCreated::dispatch($company));
    }
}
