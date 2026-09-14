<?php

namespace App\Modules\Sms\Providers;

use App\Core\Foundation\ModuleServiceProvider;

/**
 * Same shape as Storage (Module 8) and Email (Module 9): no
 * controllers/views of its own. Consumed through the Integrations
 * Center's config('integrations.testers') for Test Connection, and
 * through App\Modules\Sms\Services\SmsManager for actually sending a
 * message (config('integrations.sms_drivers')) once a real feature
 * needs to (Notifications, Module 12).
 */
class SmsServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
    }
}
