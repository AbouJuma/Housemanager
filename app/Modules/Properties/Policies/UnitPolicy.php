<?php

namespace App\Modules\Properties\Policies;

use App\Models\User;
use App\Modules\Properties\Models\Building;
use App\Modules\Properties\Models\Unit;

/**
 * Governed by the same 'properties.*' permissions as PropertyPolicy -
 * see BuildingPolicy's docblock for why.
 */
class UnitPolicy
{
    public function view(User $user, Unit $unit): bool
    {
        return $user->company_id === $unit->company_id && $user->hasPermissionTo('properties.view');
    }

    /**
     * $building is the parent a new unit would belong to - passed
     * explicitly since a Unit doesn't exist yet to check against.
     */
    public function create(User $user, Building $building): bool
    {
        return $user->company_id === $building->company_id && $user->hasPermissionTo('properties.create');
    }

    public function update(User $user, Unit $unit): bool
    {
        return $user->company_id === $unit->company_id && $user->hasPermissionTo('properties.update');
    }

    public function delete(User $user, Unit $unit): bool
    {
        return $user->company_id === $unit->company_id && $user->hasPermissionTo('properties.delete');
    }
}
