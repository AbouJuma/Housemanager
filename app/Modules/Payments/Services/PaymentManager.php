<?php

namespace App\Modules\Payments\Services;

use App\Modules\Companies\Models\Company;
use App\Modules\Integrations\Models\Integration;
use App\Modules\Payments\Contracts\PaymentGatewayInterface;

/**
 * Company-scoped entry point for moving money. charge() uses whichever
 * gateway a company currently has switched on (Module 7's Integrations
 * Center) - the only sensible default for a *new* charge. verify() and
 * refund() instead take an explicit $provider, because they act on a
 * transaction that was already charged through a *specific* gateway -
 * one a company might since have disabled - not "whichever is active
 * right now". Every method returns a result array rather than
 * throwing: "no active provider" and "the gateway declined it" are
 * both just outcomes a caller (Rent Collection, Module 18) branches
 * on, not exceptional states.
 */
class PaymentManager
{
    public function charge(Company $company, int $amountMinor, string $currency, string $reference, array $meta = []): array
    {
        $integration = Integration::query()
            ->forCompany($company->id)
            ->where('type', 'payment')
            ->where('is_active', true)
            ->first();

        if (! $integration) {
            return ['success' => false, 'message' => 'No active payment provider configured for this company.'];
        }

        $driver = $this->driverFor($integration);

        if (! $driver) {
            return ['success' => false, 'message' => "No payment driver registered for [{$integration->provider}]."];
        }

        return $driver->charge($integration, $amountMinor, $currency, $reference, $meta);
    }

    public function verify(Company $company, string $provider, string $reference): array
    {
        return $this->delegate($company, $provider, fn (PaymentGatewayInterface $driver, Integration $integration) => $driver->verify($integration, $reference));
    }

    public function refund(Company $company, string $provider, string $reference, ?int $amountMinor = null, ?string $currency = null): array
    {
        return $this->delegate($company, $provider, fn (PaymentGatewayInterface $driver, Integration $integration) => $driver->refund($integration, $reference, $amountMinor, $currency));
    }

    public function driverFor(Integration $integration): ?PaymentGatewayInterface
    {
        $class = config("integrations.payment_drivers.{$integration->provider}");

        if (! $class || ! is_subclass_of($class, PaymentGatewayInterface::class)) {
            return null;
        }

        return app($class);
    }

    protected function delegate(Company $company, string $provider, \Closure $call): array
    {
        $integration = Integration::query()
            ->forCompany($company->id)
            ->where('type', 'payment')
            ->where('provider', $provider)
            ->first();

        if (! $integration) {
            return ['success' => false, 'message' => "No {$provider} integration configured for this company."];
        }

        $driver = $this->driverFor($integration);

        if (! $driver) {
            return ['success' => false, 'message' => "No payment driver registered for [{$provider}]."];
        }

        return $call($driver, $integration);
    }
}
