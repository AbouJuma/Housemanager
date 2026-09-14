<?php

namespace App\Modules\Users\Policies;

use App\Models\User;

/**
 * SaaS-owner staff bypass every check (support/admin access). A company
 * user's ability is governed by their Spatie permissions *and* a hard
 * same-company boundary - having the permission never lets you touch
 * another company's users.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSaasOwner() || $user->hasPermissionTo('users.viewAny');
    }

    public function view(User $user, User $target): bool
    {
        if ($user->isSaasOwner()) {
            return true;
        }

        return $user->company_id === $target->company_id && $user->hasPermissionTo('users.view');
    }

    public function create(User $user): bool
    {
        return $user->isSaasOwner() || $user->hasPermissionTo('users.create');
    }

    public function update(User $user, User $target): bool
    {
        if ($user->isSaasOwner()) {
            return true;
        }

        return $user->company_id === $target->company_id && $user->hasPermissionTo('users.update');
    }

    public function delete(User $user, User $target): bool
    {
        if ($user->is($target)) {
            return false;
        }

        if ($user->isSaasOwner()) {
            return true;
        }

        return $user->company_id === $target->company_id && $user->hasPermissionTo('users.delete');
    }
}
