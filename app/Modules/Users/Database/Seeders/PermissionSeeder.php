<?php

namespace App\Modules\Users\Database\Seeders;

use App\Core\Tenancy\PermissionsTeam;
use App\Modules\Companies\Models\Company;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        collect(config('permissions'))
            ->flatMap(fn (array $group) => array_keys($group))
            ->each(fn (string $name) => Permission::findOrCreate($name, 'web'));

        // A module built after another company already exists adds new
        // permissions that company's Admin role never got (it was
        // synced with Permission::all() at the time it was created -
        // see SeedDefaultRolesForCompany). Re-running this seeder after
        // deploying such a module (`php artisan db:seed --class=...`)
        // backfills every existing company's Admin role with the
        // now-current full permission set. No-op for brand-new
        // companies, whose Admin role is already complete.
        Company::query()->each(function (Company $company): void {
            PermissionsTeam::runAs($company->id, function (): void {
                $admin = Role::findOrCreate('Admin', 'web');
                $admin->syncPermissions(Permission::query()->where('guard_name', 'web')->get());
            });
        });
    }
}
