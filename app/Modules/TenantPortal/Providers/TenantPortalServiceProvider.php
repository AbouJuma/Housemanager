<?php

namespace App\Modules\TenantPortal\Providers;

use App\Core\Foundation\ModuleServiceProvider;

/**
 * No repository/policy bindings - unlike every other module, TenantPortal
 * has no Eloquent resource of its own (it operates on Tenants module's
 * own Tenant model) and no Spatie-permission-gated policy (tenants
 * aren't part of the staff Users/Roles/Permissions system at all -
 * ownership is checked directly in each controller instead). This
 * provider exists purely so ModuleManager auto-loads the module's
 * migrations/routes/views by convention.
 */
class TenantPortalServiceProvider extends ModuleServiceProvider
{
}
