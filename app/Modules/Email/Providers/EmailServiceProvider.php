<?php

namespace App\Modules\Email\Providers;

use App\Core\Foundation\ModuleServiceProvider;

/**
 * Email has no controllers/views of its own - same shape as Storage
 * (Module 8): pure plumbing consumed by the Integrations Center's
 * "Test Connection" (config('integrations.testers')) and, later, by
 * whichever module actually sends business email (Notifications,
 * Module 12). This provider exists only so the module is discovered.
 */
class EmailServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
    }
}
