<?php

namespace App\Modules\Users\Services;

use App\Core\Tenancy\PermissionsTeam;
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
            $role->update(['name' => $attributes['name']]);
            $role->syncPermissions($attributes['permissions'] ?? []);

            return $role->fresh();
        }));
    }

    public function delete(Role $role): bool
    {
        return PermissionsTeam::runAs($role->company_id, fn () => (bool) $role->delete());
    }
}
