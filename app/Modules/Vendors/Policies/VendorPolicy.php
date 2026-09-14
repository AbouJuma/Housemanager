<?php

namespace App\Modules\Vendors\Policies;

use App\Models\User;
use App\Modules\Vendors\Models\Vendor;

/**
 * Same no-SaaS-owner-bypass pattern as LandlordPolicy (ARCHITECTURE.md
 * §10b) - a vendor record, including its bank details, is operational
 * business data belonging to exactly one company.
 */
class VendorPolicy
{
    public function viewAny(User $user): bool
    {
        return ! $user->isSaasOwner() && $user->hasPermissionTo('vendors.viewAny');
    }

    public function view(User $user, Vendor $vendor): bool
    {
        return $user->company_id === $vendor->company_id && $user->hasPermissionTo('vendors.view');
    }

    public function create(User $user): bool
    {
        return ! $user->isSaasOwner() && $user->hasPermissionTo('vendors.create');
    }

    public function update(User $user, Vendor $vendor): bool
    {
        return $user->company_id === $vendor->company_id && $user->hasPermissionTo('vendors.update');
    }

    public function delete(User $user, Vendor $vendor): bool
    {
        return $user->company_id === $vendor->company_id && $user->hasPermissionTo('vendors.delete');
    }
}
