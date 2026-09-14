<?php

namespace App\Modules\Subscriptions\Policies;

use App\Models\User;
use App\Modules\Subscriptions\Models\SubscriptionPlan;

/**
 * SaaS-owner only, full stop - the same shape as CompanyPolicy's own
 * viewAny/create/delete (ARCHITECTURE.md §2), not the "no-SaaS-owner-
 * bypass" pattern every tenant-facing domain policy uses. Plans are a
 * platform-level catalog no company user ever needs to browse or edit.
 */
class SubscriptionPlanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSaasOwner();
    }

    public function view(User $user, SubscriptionPlan $plan): bool
    {
        return $user->isSaasOwner();
    }

    public function create(User $user): bool
    {
        return $user->isSaasOwner();
    }

    public function update(User $user, SubscriptionPlan $plan): bool
    {
        return $user->isSaasOwner();
    }

    public function delete(User $user, SubscriptionPlan $plan): bool
    {
        return $user->isSaasOwner();
    }
}
