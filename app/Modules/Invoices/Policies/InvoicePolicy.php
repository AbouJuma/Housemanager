<?php

namespace App\Modules\Invoices\Policies;

use App\Models\User;
use App\Modules\Invoices\Models\Invoice;

/**
 * Same no-SaaS-owner-bypass pattern as LeasePolicy (ARCHITECTURE.md
 * §10d) - an invoice, including its amounts and payment history, is
 * operational business data belonging to exactly one company. Status
 * transitions (send/cancel/recordPayment) and deletion all still just
 * check `update`/`delete` - there's no separate permission per action,
 * the same restraint LeasePolicy applies to termination.
 */
class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return ! $user->isSaasOwner() && $user->hasPermissionTo('invoices.viewAny');
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->company_id === $invoice->company_id && $user->hasPermissionTo('invoices.view');
    }

    public function create(User $user): bool
    {
        return ! $user->isSaasOwner() && $user->hasPermissionTo('invoices.create');
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->company_id === $invoice->company_id && $user->hasPermissionTo('invoices.update');
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->company_id === $invoice->company_id && $user->hasPermissionTo('invoices.delete');
    }
}
