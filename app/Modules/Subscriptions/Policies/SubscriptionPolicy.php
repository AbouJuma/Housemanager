<?php

namespace App\Modules\Subscriptions\Policies;

use App\Models\User;
use App\Modules\Subscriptions\Models\Subscription;

/**
 * The same "SaaS owner manages all, a company's own users may only view
 * their own" shape as CompanyPolicy itself (ARCHITECTURE.md §2) - no
 * separate permission gate on `view`, for the identical reason: any of
 * a company's own users can already see their own company's basic
 * details without a dedicated permission, and a subscription is exactly
 * that kind of fact about the company they belong to.
 */
class SubscriptionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSaasOwner();
    }

    public function view(User $user, Subscription $subscription): bool
    {
        return $user->isSaasOwner() || $user->company_id === $subscription->company_id;
    }

    public function create(User $user): bool
    {
        return $user->isSaasOwner();
    }

    public function update(User $user, Subscription $subscription): bool
    {
        return $user->isSaasOwner();
    }
}
