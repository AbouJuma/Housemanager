<?php

namespace App\Modules\Inventory\Policies;

use App\Models\User;
use App\Modules\Inventory\Models\InventoryItem;

/**
 * Same no-SaaS-owner-bypass pattern as MaintenanceRequestPolicy/
 * VendorPolicy (ARCHITECTURE.md §14/§15) - a company's stock is its own
 * data. recordTransaction is gated by `update`, not its own permission
 * - the same restraint applied to MaintenanceRequest's recordExpense.
 */
class InventoryItemPolicy
{
    public function viewAny(User $user): bool
    {
        return ! $user->isSaasOwner() && $user->hasPermissionTo('inventory.viewAny');
    }

    public function view(User $user, InventoryItem $item): bool
    {
        return $user->company_id === $item->company_id && $user->hasPermissionTo('inventory.view');
    }

    public function create(User $user): bool
    {
        return ! $user->isSaasOwner() && $user->hasPermissionTo('inventory.create');
    }

    public function update(User $user, InventoryItem $item): bool
    {
        return $user->company_id === $item->company_id && $user->hasPermissionTo('inventory.update');
    }

    public function delete(User $user, InventoryItem $item): bool
    {
        return $user->company_id === $item->company_id && $user->hasPermissionTo('inventory.delete');
    }
}
