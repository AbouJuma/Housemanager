<?php

namespace App\Modules\Reports\Providers;

use App\Core\Foundation\ModuleServiceProvider;

/**
 * No repository/policy bindings - every report is a stateless
 * aggregation service, not an Eloquent-backed resource, and
 * authorization is a single permission string checked directly
 * (ReportController's own doc block explains why). This provider exists
 * purely so ModuleManager auto-loads the module's routes/views.
 */
class ReportsServiceProvider extends ModuleServiceProvider
{
}
