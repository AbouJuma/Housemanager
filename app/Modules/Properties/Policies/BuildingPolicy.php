<?php

namespace App\Modules\Properties\Policies;

use App\Models\User;
use App\Modules\Properties\Models\Building;
use App\Modules\Properties\Models\Property;

/**
 * Governed by the same 'properties.*' permissions as PropertyPolicy -
 * buildings/units are always managed in the context of managing
 * properties, a single hierarchical concern rather than three
 * independently permissioned ones. See config/permissions.php.
 */
class BuildingPolicy
{
    public function view(User $user, Building $building): bool
    {
        return $user->company_id === $building->company_id && $user->hasPermissionTo('properties.view');
    }

    /**
     * $property is the parent a new building would belong to - passed
     * explicitly since a Building doesn't exist yet to check against.
     */
    public function create(User $user, Property $property): bool
    {
        return $user->company_id === $property->company_id && $user->hasPermissionTo('properties.create');
    }

    public function update(User $user, Building $building): bool
    {
        return $user->company_id === $building->company_id && $user->hasPermissionTo('properties.update');
    }

    public function delete(User $user, Building $building): bool
    {
        return $user->company_id === $building->company_id && $user->hasPermissionTo('properties.delete');
    }
}
