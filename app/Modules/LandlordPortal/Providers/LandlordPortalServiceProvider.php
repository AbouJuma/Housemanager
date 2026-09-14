<?php

namespace App\Modules\LandlordPortal\Providers;

use App\Core\Foundation\ModuleServiceProvider;

/**
 * No repository/policy bindings - the same reasoning as Module 26's
 * TenantPortalServiceProvider: no Eloquent resource of its own, and
 * landlords aren't part of the staff Users/Roles/Permissions system, so
 * ownership is checked directly in each controller instead of a Policy.
 */
class LandlordPortalServiceProvider extends ModuleServiceProvider
{
}
