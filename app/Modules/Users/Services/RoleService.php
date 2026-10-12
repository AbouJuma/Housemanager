<?php

namespace App\Modules\Users\Services;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Landlords\Models\Landlord;
use App\Modules\LandlordPortal\Support\LandlordAccess;
use App\Modules\Users\Notifications\AccessChanged;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

/**
 * Role isn't one of our own domain models (it's Spatie's), so this
 * skips the Repository indirection every other module uses and talks
 * to Role directly - the meaningful logic here is entirely about
 * getting the teams team-context switch right, not CRUD plumbing.
 *
 * Unlike our own BelongsToCompany-scoped models, Spatie's Role model
 * has NO automatic global scope filtering plain queries by team - the
 * team id set via PermissionsTeam::runAs() only affects
 * create()/findOrCreate()-style calls (which auto-stamp company_id
 * from it) and specific team-aware lookups. A plain `Role::query()`
 * still returns every company's roles, so listing queries filter by
 * `company_id` explicitly rather than relying on scoping.
 */
class RoleService
{
    public function paginate(int $companyId, ?string $search, int $perPage = 15): LengthAwarePaginator
    {
        return Role::query()
            ->where('company_id', $companyId)
            ->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(int $companyId, array $attributes): Role
    {
        return PermissionsTeam::runAs($companyId, fn () => DB::transaction(function () use ($attributes) {
            $role = Role::create(['name' => $attributes['name'], 'guard_name' => 'web']);
            $role->syncPermissions($attributes['permissions'] ?? []);

            return $role;
        }));
    }

    public function update(Role $role, array $attributes): Role
    {
        return PermissionsTeam::runAs($role->company_id, fn () => DB::transaction(function () use ($role, $attributes) {
            $before = $role->permissions->pluck('name')->all();
            $forLandlords = LandlordAccess::isLandlordRole($role);

            if ($forLandlords) {
                // Fixed name; only landlord permissions can be ticked.
                LandlordAccess::ensurePermissions();
                $role->syncPermissions(array_values(array_intersect($attributes['permissions'] ?? [], LandlordAccess::names())));
            } else {
                $role->update(['name' => $attributes['name']]);
                $role->syncPermissions($attributes['permissions'] ?? []);
            }

            $after = $role->fresh()->permissions->pluck('name')->all();
            $granted = array_values(array_diff($after, $before));
            $revoked = array_values(array_diff($before, $after));

            if (($granted || $revoked) && $forLandlords) {
                foreach (Landlord::query()->where('company_id', $role->company_id)->whereNotNull('password')->get() as $landlord) {
                    $landlord->notify(new AccessChanged($granted, $revoked, [$role->name]));
                }
            } elseif ($granted || $revoked) {
                foreach (User::query()->role($role->name)->where('company_id', $role->company_id)->get() as $user) {
                    $user->notify(new AccessChanged($granted, $revoked, $user->getRoleNames()->all()));
                }
            }

            return $role->fresh();
        }));
    }

    public function delete(Role $role): bool
    {
        // The Landlord role can be edited but never removed.
        abort_if(LandlordAccess::isLandlordRole($role), 403);

        return PermissionsTeam::runAs($role->company_id, fn () => (bool) $role->delete());
    }
}
