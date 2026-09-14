<?php

namespace App\Modules\Integrations\Policies;

use App\Models\User;
use App\Modules\Integrations\Models\Integration;

/**
 * Same pattern as RolePolicy/SettingsPolicy: an integration only makes
 * sense within one specific company, so SaaS-owner staff do NOT
 * bypass here.
 */
class IntegrationPolicy
{
    public function viewAny(User $user): bool
    {
        return ! $user->isSaasOwner() && $user->hasPermissionTo('integrations.view');
    }

    public function view(User $user, Integration $integration): bool
    {
        return $user->company_id === $integration->company_id && $user->hasPermissionTo('integrations.view');
    }

    public function update(User $user, Integration $integration): bool
    {
        return $user->company_id === $integration->company_id && $user->hasPermissionTo('integrations.update');
    }
}
