<?php

namespace App\Modules\Users\Listeners;

use App\Core\Tenancy\PermissionsTeam;
use App\Modules\Companies\Events\CompanyCreated;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SeedDefaultRolesForCompany
{
    public function handle(CompanyCreated $event): void
    {
        PermissionsTeam::runAs($event->company->id, function (): void {
            $admin = Role::findOrCreate('Admin', 'web');
            $admin->syncPermissions(Permission::query()->where('guard_name', 'web')->get());

            $staff = Role::findOrCreate('Staff', 'web');
            $staff->syncPermissions(
                Permission::query()->where('guard_name', 'web')->where('name', 'like', '%.view%')->get()
            );
        });
    }
}
