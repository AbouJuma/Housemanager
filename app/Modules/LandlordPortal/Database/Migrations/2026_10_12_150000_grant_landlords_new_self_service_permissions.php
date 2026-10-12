<?php

use App\Core\Tenancy\PermissionsTeam;
use App\Modules\LandlordPortal\Support\LandlordAccess;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Landlords can now add buildings, units and expenses, and edit their
 * tenants and buyers. Companies whose "Landlord" role already exists get
 * the new permissions switched on, the same
 * as a brand-new role would (admins can untick them in Settings -> Roles).
 */
return new class extends Migration
{
    public function up(): void
    {
        LandlordAccess::ensurePermissions();

        Role::query()->where('guard_name', LandlordAccess::GUARD)->where('name', LandlordAccess::ROLE)->get()
            ->each(fn (Role $role) => PermissionsTeam::runAs(
                $role->company_id,
                fn () => $role->givePermissionTo(['units.create', 'units.update', 'tenants.update', 'buyers.viewAny', 'buyers.update', 'expenses.create', 'expenses.update', 'invoices.viewAny']),
            ));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Leave the permissions in place - removing them could lock
        // landlords out of something an admin chose to allow.
    }
};
