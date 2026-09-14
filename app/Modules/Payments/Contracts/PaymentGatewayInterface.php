<?php

namespace App\Modules\Payments\Contracts;

use App\Modules\Integrations\Models\Integration;

/**
 * Implemented by each real payment gateway driver
 * (App\Modules\Payments\Drivers). Amounts are always integer minor
 * units (cents) - never floats - to avoid the rounding bugs floating
 * point money handling invites; each driver converts to whatever
 * shape its own API expects.
 */
interface PaymentGatewayInterface
{
    /**
     * @param  string  $reference  A caller-supplied unique reference, reused as the gateway's idempotency key so retries can't double-charge.
     * @return array{success: bool, message: string, reference?: string, redirect_url?: string, status?: string}
     */
    public function charge(Integration $integration, int $amountMinor, string $currency, string $reference, array $meta = []): array;

    /**
     * @return array{success: bool, message: string, status?: string}
     */
    public function verify(Integration $integration, string $reference): array;

    /**
     * @return array{success: bool, message: string}
     */
    public function refund(Integration $integration, string $reference, ?int $amountMinor = null, ?string $currency = null): array;
}
