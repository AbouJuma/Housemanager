<?php

namespace App\Modules\Landlords\Policies;

use App\Models\User;
use App\Modules\Landlords\Models\Landlord;

/**
 * Same no-SaaS-owner-bypass pattern as PropertyPolicy (ARCHITECTURE.md
 * §10a) - a landlord record, including its bank details, is operational
 * business data belonging to exactly one company.
 */
class LandlordPolicy
{
    public function viewAny(User $user): bool
    {
        return ! $user->isSaasOwner() && $user->hasPermissionTo('landlords.viewAny');
    }

    public function view(User $user, Landlord $landlord): bool
    {
        return $user->company_id === $landlord->company_id && $user->hasPermissionTo('landlords.view');
    }

    public function create(User $user): bool
    {
        return ! $user->isSaasOwner() && $user->hasPermissionTo('landlords.create');
    }

    public function update(User $user, Landlord $landlord): bool
    {
        return $user->company_id === $landlord->company_id && $user->hasPermissionTo('landlords.update');
    }

    public function delete(User $user, Landlord $landlord): bool
    {
        return $user->company_id === $landlord->company_id && $user->hasPermissionTo('landlords.delete');
    }
}
