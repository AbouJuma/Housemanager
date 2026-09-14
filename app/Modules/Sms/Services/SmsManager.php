<?php

namespace App\Modules\Sms\Services;

use App\Modules\Companies\Models\Company;
use App\Modules\Integrations\Models\Integration;
use App\Modules\Sms\Contracts\SmsProviderInterface;

/**
 * Company-scoped entry point for sending an SMS: resolves whichever
 * gateway a company has switched on (Module 7's Integrations Center)
 * and delegates to its driver (config('integrations.sms_drivers')).
 * Failures are returned, not thrown - "no active provider" and "the
 * gateway rejected the message" are both just results a caller (a
 * future Notifications job) needs to branch on, not exceptional states.
 */
class SmsManager
{
    public function send(Company $company, string $to, string $message): array
    {
        $integration = Integration::query()
            ->forCompany($company->id)
            ->where('type', 'sms')
            ->where('is_active', true)
            ->first();

        if (! $integration) {
            return ['success' => false, 'message' => 'No active SMS provider configured for this company.'];
        }

        $driver = $this->driverFor($integration);

        if (! $driver) {
            return ['success' => false, 'message' => "No SMS driver registered for [{$integration->provider}]."];
        }

        return $driver->send($integration, $to, $message);
    }

    public function driverFor(Integration $integration): ?SmsProviderInterface
    {
        $class = config("integrations.sms_drivers.{$integration->provider}");

        if (! $class || ! is_subclass_of($class, SmsProviderInterface::class)) {
            return null;
        }

        return app($class);
    }
}
