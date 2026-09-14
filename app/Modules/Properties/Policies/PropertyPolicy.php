<?php

namespace App\Modules\Properties\Policies;

use App\Models\User;
use App\Modules\Properties\Models\Property;

/**
 * Same pattern as RolePolicy/SettingsPolicy/IntegrationPolicy: SaaS-owner
 * staff do NOT bypass these checks. A property is operational business
 * data belonging to exactly one company - the SaaS owner has no
 * "current company" to manage properties within, unlike Companies/Users
 * which the SaaS owner administers directly.
 */
class PropertyPolicy
{
    public function viewAny(User $user): bool
    {
        return ! $user->isSaasOwner() && $user->hasPermissionTo('properties.viewAny');
    }

    public function view(User $user, Property $property): bool
    {
        return $user->company_id === $property->company_id && $user->hasPermissionTo('properties.view');
    }

    public function create(User $user): bool
    {
        return ! $user->isSaasOwner() && $user->hasPermissionTo('properties.create');
    }

    public function update(User $user, Property $property): bool
    {
        return $user->company_id === $property->company_id && $user->hasPermissionTo('properties.update');
    }

    public function delete(User $user, Property $property): bool
    {
        return $user->company_id === $property->company_id && $user->hasPermissionTo('properties.delete');
    }
}
