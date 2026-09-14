<?php

namespace App\Modules\VisitorManagement\Policies;

use App\Models\User;
use App\Modules\VisitorManagement\Models\Visitor;

/**
 * Same no-SaaS-owner-bypass pattern as every other Phase D domain
 * policy (ARCHITECTURE.md §15/§16/§17) - a company's visitor log is its
 * own. checkOut is gated by `update`, not its own permission.
 */
class VisitorPolicy
{
    public function viewAny(User $user): bool
    {
        return ! $user->isSaasOwner() && $user->hasPermissionTo('visitors.viewAny');
    }

    public function view(User $user, Visitor $visitor): bool
    {
        return $user->company_id === $visitor->company_id && $user->hasPermissionTo('visitors.view');
    }

    public function create(User $user): bool
    {
        return ! $user->isSaasOwner() && $user->hasPermissionTo('visitors.create');
    }

    public function update(User $user, Visitor $visitor): bool
    {
        return $user->company_id === $visitor->company_id && $user->hasPermissionTo('visitors.update');
    }

    public function delete(User $user, Visitor $visitor): bool
    {
        return $user->company_id === $visitor->company_id && $user->hasPermissionTo('visitors.delete');
    }
}
