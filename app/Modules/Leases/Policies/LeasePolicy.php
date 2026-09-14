<?php

namespace App\Modules\Leases\Policies;

use App\Models\User;
use App\Modules\Leases\Models\Lease;

/**
 * Same no-SaaS-owner-bypass pattern as LandlordPolicy/TenantPolicy
 * (ARCHITECTURE.md §10b/§10c) - a lease, including its rent/deposit
 * terms, is operational business data belonging to exactly one company.
 */
class LeasePolicy
{
    public function viewAny(User $user): bool
    {
        return ! $user->isSaasOwner() && $user->hasPermissionTo('leases.viewAny');
    }

    public function view(User $user, Lease $lease): bool
    {
        return $user->company_id === $lease->company_id && $user->hasPermissionTo('leases.view');
    }

    public function create(User $user): bool
    {
        return ! $user->isSaasOwner() && $user->hasPermissionTo('leases.create');
    }

    public function update(User $user, Lease $lease): bool
    {
        return $user->company_id === $lease->company_id && $user->hasPermissionTo('leases.update');
    }

    public function delete(User $user, Lease $lease): bool
    {
        return $user->company_id === $lease->company_id && $user->hasPermissionTo('leases.delete');
    }
}
