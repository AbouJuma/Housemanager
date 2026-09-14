<?php

namespace App\Modules\Accounting\Policies;

use App\Models\User;
use App\Modules\Accounting\Models\Account;

/**
 * Same no-SaaS-owner-bypass pattern as InvoicePolicy (ARCHITECTURE.md
 * §10e) - a company's chart of accounts is its own internal financial
 * structure, not something the SaaS owner administers.
 */
class AccountPolicy
{
    public function viewAny(User $user): bool
    {
        return ! $user->isSaasOwner() && $user->hasPermissionTo('accounts.viewAny');
    }

    public function view(User $user, Account $account): bool
    {
        return $user->company_id === $account->company_id && $user->hasPermissionTo('accounts.view');
    }

    public function create(User $user): bool
    {
        return ! $user->isSaasOwner() && $user->hasPermissionTo('accounts.create');
    }

    public function update(User $user, Account $account): bool
    {
        return $user->company_id === $account->company_id && $user->hasPermissionTo('accounts.update');
    }

    public function delete(User $user, Account $account): bool
    {
        return $user->company_id === $account->company_id && $user->hasPermissionTo('accounts.delete');
    }
}
