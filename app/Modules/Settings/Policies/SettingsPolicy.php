<?php

namespace App\Modules\Settings\Policies;

use App\Models\User;

/**
 * Not a model policy (Setting rows aren't authorized individually) -
 * these gate the two settings pages. Same pattern as RolePolicy:
 * company settings only make sense within one specific company, so
 * SaaS-owner staff (no company) don't get a bypass there; system
 * settings are the reverse, gated purely on isSaasOwner().
 */
class SettingsPolicy
{
    public function viewCompany(User $user): bool
    {
        return ! $user->isSaasOwner() && $user->hasPermissionTo('settings.view');
    }

    public function updateCompany(User $user): bool
    {
        return ! $user->isSaasOwner() && $user->hasPermissionTo('settings.update');
    }

    public function viewSystem(User $user): bool
    {
        return $user->isSaasOwner();
    }

    public function updateSystem(User $user): bool
    {
        return $user->isSaasOwner();
    }
}
