<?php

namespace App\Modules\Users\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;

/**
 * Unlike other policies in this app, SaaS-owner staff do NOT bypass
 * these checks: a role only makes sense within a specific company's
 * team scope, and the SaaS owner (company_id null) has no "current
 * company" to manage roles within. Bootstrapping a brand-new company's
 * first Admin/Staff roles happens automatically via
 * SeedDefaultRolesForCompany, and the SaaS owner can still assign an
 * existing role to a user in any company through the Users edit page
 * (App\Modules\Users\Http\Controllers\UserController::rolesForCompany
 * explicitly switches team context) - only the standalone role
 * editor is restricted to company-scoped admins.
 *
 * Route-model-binding a {role} does NOT filter by company (Spatie's
 * Role model has no such global scope - see App\Modules\Users\Services
 * \RoleService's docblock), so the explicit company_id comparison
 * below is the only thing stopping a company admin from viewing/
 * editing another company's role by guessing its id.
 */
class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return ! $user->isSaasOwner() && $user->hasPermissionTo('roles.viewAny');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->company_id === $role->company_id && $user->hasPermissionTo('roles.view');
    }

    public function create(User $user): bool
    {
        return ! $user->isSaasOwner() && $user->hasPermissionTo('roles.create');
    }

    public function update(User $user, Role $role): bool
    {
        return $user->company_id === $role->company_id && $user->hasPermissionTo('roles.update');
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->company_id === $role->company_id && $user->hasPermissionTo('roles.delete');
    }
}
