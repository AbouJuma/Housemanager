<?php

namespace App\Modules\BuyerPortal\Providers;

use App\Core\Foundation\ModuleServiceProvider;

/**
 * No repository/policy bindings - mirrors App\Modules\TenantPortal\
 * Providers\TenantPortalServiceProvider: this module operates on the
 * Sales module's own Buyer model and has no Spatie-permission-gated
 * resource of its own. Exists purely so ModuleManager auto-loads the
 * module's migrations/routes by convention.
 */
class BuyerPortalServiceProvider extends ModuleServiceProvider
{
}
