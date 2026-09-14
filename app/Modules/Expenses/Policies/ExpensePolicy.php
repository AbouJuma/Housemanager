<?php

namespace App\Modules\Expenses\Policies;

use App\Models\User;
use App\Modules\Expenses\Models\Expense;

/**
 * Same no-SaaS-owner-bypass pattern as AccountPolicy (ARCHITECTURE.md
 * §12) - a company's expenses are its own operational/financial data.
 * No `delete` method - ExpenseService::delete() always rejects, so
 * there's nothing for a delete permission to gate (ARCHITECTURE.md
 * §13); `void` covers retraction instead, gated by `update` like
 * Invoice's send/cancel/recordPayment actions (§10e) rather than its
 * own permission - unlike JournalEntry's void (§12), which has no
 * `update` action at all to fold into, Expense does (editing its
 * metadata), so void reuses it.
 */
class ExpensePolicy
{
    public function viewAny(User $user): bool
    {
        return ! $user->isSaasOwner() && $user->hasPermissionTo('expenses.viewAny');
    }

    public function view(User $user, Expense $expense): bool
    {
        return $user->company_id === $expense->company_id && $user->hasPermissionTo('expenses.view');
    }

    public function create(User $user): bool
    {
        return ! $user->isSaasOwner() && $user->hasPermissionTo('expenses.create');
    }

    public function update(User $user, Expense $expense): bool
    {
        return $user->company_id === $expense->company_id && $user->hasPermissionTo('expenses.update');
    }
}
