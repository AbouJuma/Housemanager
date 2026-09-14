<?php

namespace App\Modules\Payments\Providers;

use App\Core\Foundation\ModuleServiceProvider;

/**
 * Same shape as Storage/Email/SMS: no controllers/views of its own.
 * Consumed through the Integrations Center's config('integrations.testers')
 * for Test Connection (all twelve payment providers), and through
 * App\Modules\Payments\Services\PaymentManager for actually moving
 * money once a real feature needs to (Rent Collection, Module 18).
 */
class PaymentsServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
    }
}
