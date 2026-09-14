<?php

namespace App\Modules\Maintenance\Policies;

use App\Models\User;
use App\Modules\Maintenance\Models\MaintenanceRequest;

/**
 * Same no-SaaS-owner-bypass pattern as ExpensePolicy/VendorPolicy
 * (ARCHITECTURE.md §13/§14) - a maintenance request is a company's own
 * operational data. recordExpense is gated by `update`, not its own
 * permission - the same restraint applied to Invoice's send/cancel/
 * recordPayment actions (§10e).
 */
class MaintenanceRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return ! $user->isSaasOwner() && $user->hasPermissionTo('maintenance.viewAny');
    }

    public function view(User $user, MaintenanceRequest $request): bool
    {
        return $user->company_id === $request->company_id && $user->hasPermissionTo('maintenance.view');
    }

    public function create(User $user): bool
    {
        return ! $user->isSaasOwner() && $user->hasPermissionTo('maintenance.create');
    }

    public function update(User $user, MaintenanceRequest $request): bool
    {
        return $user->company_id === $request->company_id && $user->hasPermissionTo('maintenance.update');
    }

    public function delete(User $user, MaintenanceRequest $request): bool
    {
        return $user->company_id === $request->company_id && $user->hasPermissionTo('maintenance.delete');
    }
}
