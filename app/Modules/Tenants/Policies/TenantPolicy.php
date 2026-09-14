<?php

namespace App\Modules\Tenants\Policies;

use App\Models\User;
use App\Modules\Tenants\Models\Tenant;

/**
 * Same no-SaaS-owner-bypass pattern as PropertyPolicy/LandlordPolicy
 * (ARCHITECTURE.md §10a/§10b). Tenants get their own `tenants.*`
 * permission group, separate from `properties.*`/`landlords.*` - a
 * tenant record holds sensitive personal data (identification number,
 * date of birth) that not every property-editing user needs access to,
 * the same reasoning that gave Landlords its own permission group.
 */
class TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return ! $user->isSaasOwner() && $user->hasPermissionTo('tenants.viewAny');
    }

    public function view(User $user, Tenant $tenant): bool
    {
        return $user->company_id === $tenant->company_id && $user->hasPermissionTo('tenants.view');
    }

    public function create(User $user): bool
    {
        return ! $user->isSaasOwner() && $user->hasPermissionTo('tenants.create');
    }

    public function update(User $user, Tenant $tenant): bool
    {
        return $user->company_id === $tenant->company_id && $user->hasPermissionTo('tenants.update');
    }

    public function delete(User $user, Tenant $tenant): bool
    {
        return $user->company_id === $tenant->company_id && $user->hasPermissionTo('tenants.delete');
    }
}
