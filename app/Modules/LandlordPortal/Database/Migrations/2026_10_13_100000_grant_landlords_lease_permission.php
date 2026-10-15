<?php

use App\Core\Tenancy\PermissionsTeam;
use App\Modules\LandlordPortal\Support\LandlordAccess;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Landlords can now rent out their own units (create leases). Existing
 * "Landlord" roles get it switched on; admins can untick it any time.
 */
return new class extends Migration
{
    public function up(): void
    {
        LandlordAccess::ensurePermissions();

        Role::query()->where('guard_name', LandlordAccess::GUARD)->where('name', LandlordAccess::ROLE)->get()
            ->each(fn (Role $role) => PermissionsTeam::runAs($role->company_id, fn () => $role->givePermissionTo('leases.create')));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        //
    }
};
