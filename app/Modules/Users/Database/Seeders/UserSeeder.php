<?php

namespace App\Modules\Users\Database\Seeders;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        Company::query()->each(function (Company $company): void {
            $admin = User::factory()->create([
                'name' => "{$company->name} Admin",
                'email' => "admin@{$company->slug}.test",
                'company_id' => $company->id,
            ]);

            PermissionsTeam::runAs($company->id, fn () => $admin->assignRole('Admin'));
        });
    }
}
