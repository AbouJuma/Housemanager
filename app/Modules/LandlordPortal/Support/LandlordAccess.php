<?php

namespace App\Modules\LandlordPortal\Support;

use App\Core\Tenancy\PermissionsTeam;
use App\Modules\Landlords\Models\Landlord;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * What landlords may do in the portal and the app, kept as one editable
 * "Landlord" role per company (Settings -> Roles). It lives on the
 * 'landlord' guard, so it can never be given to staff, and staff roles
 * can never be given to landlords. Every landlord of the company shares
 * it - changing a tick box changes what all of them see, immediately.
 */
class LandlordAccess
{
    public const ROLE = 'Landlord';

    public const GUARD = 'landlord';

    /**
     * Permission => what it lets a landlord do. Names match the staff
     * permissions of the same feature, scoped to the landlord's own
     * properties and tenants.
     *
     * @var array<string, array<string, string>>
     */
    public const PERMISSIONS = [
        'properties' => [
            'properties.viewAny' => 'See their properties and units (rented / free)',
            'properties.create' => 'Add new properties',
            'properties.update' => 'Edit their properties',
        ],
        'units' => [
            'units.create' => 'Add buildings and units to their properties',
            'units.update' => 'Edit their units (rent, type, availability)',
        ],
        'tenants' => [
            'tenants.viewAny' => 'See the tenants in their properties',
            'tenants.create' => 'Add new tenants',
            'tenants.update' => 'Edit their tenants\' details',
        ],
        'buyers' => [
            'buyers.viewAny' => 'See the buyers of their units',
            'buyers.update' => 'Edit their buyers\' details',
        ],
        'invoices' => [
            'invoices.viewAny' => 'See invoices for their units (who still owes)',
        ],
        'reports' => [
            'reports.view' => 'See profit & loss and occupancy reports',
        ],
        'expenses' => [
            'expenses.viewAny' => 'See expenses on their properties',
            'expenses.create' => 'Add expenses to their properties',
            'expenses.update' => 'Edit their expenses',
        ],
        'maintenance' => [
            'maintenance.viewAny' => 'See maintenance requests on their properties',
        ],
        'documents' => [
            'documents.viewAny' => 'See their documents',
        ],
    ];

    /** @return list<string> */
    public static function names(): array
    {
        return collect(self::PERMISSIONS)->flatMap(fn (array $group) => array_keys($group))->values()->all();
    }

    /**
     * The company's Landlord role, created with every landlord permission
     * switched on the first time it's needed (so nothing changes for
     * landlords until an admin decides to restrict them).
     */
    public static function role(int $companyId): Role
    {
        return PermissionsTeam::runAs($companyId, function () {
            $existing = Role::query()->where('company_id', app(PermissionRegistrar::class)->getPermissionsTeamId())
                ->where('name', self::ROLE)->where('guard_name', self::GUARD)->first();
            if ($existing) {
                return $existing;
            }

            self::ensurePermissions();
            $role = Role::findOrCreate(self::ROLE, self::GUARD);
            $role->syncPermissions(self::names());

            return $role;
        });
    }

    public static function ensurePermissions(): void
    {
        foreach (self::names() as $name) {
            Permission::findOrCreate($name, self::GUARD);
        }
    }

    public static function isLandlordRole(Role $role): bool
    {
        return $role->guard_name === self::GUARD;
    }

    /** @return list<string> */
    public static function permissionsOf(Landlord $landlord): array
    {
        if (! $landlord->company_id) {
            return [];
        }

        return self::role($landlord->company_id)->permissions->pluck('name')->sort()->values()->all();
    }

    public static function can(?Landlord $landlord, string $permission): bool
    {
        return $landlord !== null && in_array($permission, self::permissionsOf($landlord), true);
    }

    public static function authorize(?Landlord $landlord, string $permission): void
    {
        abort_unless(self::can($landlord, $permission), 403, __('Your landlord access does not include this.'));
    }
}
