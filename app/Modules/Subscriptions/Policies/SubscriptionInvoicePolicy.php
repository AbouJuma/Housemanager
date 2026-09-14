<?php

namespace App\Modules\Subscriptions\Policies;

use App\Models\User;
use App\Modules\Subscriptions\Models\SubscriptionInvoice;

/**
 * Same shape as SubscriptionPolicy - a company's own users can view
 * (not manage) their own platform billing history.
 */
class SubscriptionInvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSaasOwner();
    }

    public function view(User $user, SubscriptionInvoice $invoice): bool
    {
        return $user->isSaasOwner() || $user->company_id === $invoice->company_id;
    }

    public function create(User $user): bool
    {
        return $user->isSaasOwner();
    }

    public function update(User $user, SubscriptionInvoice $invoice): bool
    {
        return $user->isSaasOwner();
    }
}
