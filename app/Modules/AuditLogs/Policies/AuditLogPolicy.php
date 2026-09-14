<?php

namespace App\Modules\AuditLogs\Policies;

use App\Models\User;

/**
 * Read-only (activity log rows are system-generated, never created/
 * edited/deleted by a user directly), so this is a single-ability
 * policy. Unlike RolePolicy/SettingsPolicy's company-only pattern,
 * SaaS-owner staff DO bypass here: cross-company audit visibility is
 * a legitimate platform-oversight need, not a "current company"
 * concept the way editing roles/settings is.
 */
class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSaasOwner() || $user->hasPermissionTo('audit-logs.view');
    }
}
