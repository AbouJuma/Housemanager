<?php

namespace App\Modules\Payments\Drivers;

use App\Modules\Integrations\Contracts\IntegrationTester;
use App\Modules\Integrations\Models\Integration;
use App\Modules\Payments\Contracts\PaymentGatewayInterface;
use App\Modules\Payments\Drivers\Concerns\FetchesOAuth2Token;
use Illuminate\Support\Facades\Http;

/**
 * Airtel Africa Open API (https://developers.airtel.africa) - an
 * OAuth2 client-credentials token, then a "collection" request that
 * prompts the customer's phone to approve. Airtel Africa publishes
 * this as a real developer portal (used across the several African
 * markets Airtel Money operates in, including Tanzania), giving
 * moderate-good confidence in the general shape.
 *
 * Credential mapping: api_key = Client ID, api_secret = Client Secret,
 * username = Country code (e.g. "TZ"), password = Currency code (e.g.
 * "TZS") - both required on every request by this API, not just at
 * setup, so they're stored rather than hardcoded.
 */
class AirtelMoneyDriver implements IntegrationTester, PaymentGatewayInterface
{
    use FetchesOAuth2Token;

    protected const BASE_URL = 'https://openapi.airtel.africa';

    public function charge(Integration $integration, int $amountMinor, string $currency, string $reference, array $meta = []): array
    {
        $token = $this->token($integration);

        if (! $token) {
            return ['success' => false, 'message' => 'Could not authenticate with Airtel Money.'];
        }

        $response = Http::withToken($token)
            ->withHeaders($this->countryCurrencyHeaders($integration))
            ->post(self::BASE_URL.'/merchant/v1/payments/', [
                'reference' => $reference,
                'subscriber' => [
                    'country' => $integration->credential('username'),
                    'currency' => $integration->credential('password'),
                    'msisdn' => $meta['phone'] ?? '',
                ],
                'transaction' => [
                    'amount' => $amountMinor / 100,
                    'country' => $integration->credential('username'),
                    'currency' => strtoupper($currency),
                    'id' => $reference,
                ],
            ]);

        if ($response->successful() && $response->json('status.success')) {
            return [
                'success' => true,
                'message' => $response->json('status.message') ?? 'Payment request sent to customer.',
                'reference' => $response->json('data.transaction.id') ?? $reference,
                'status' => 'pending',
            ];
        }

        return ['success' => false, 'message' => $response->json('status.message') ?? $response->body()];
    }

    public function verify(Integration $integration, string $reference): array
    {
        $token = $this->token($integration);

        if (! $token) {
            return ['success' => false, 'message' => 'Could not authenticate with Airtel Money.'];
        }

        $response = Http::withToken($token)
            ->withHeaders($this->countryCurrencyHeaders($integration))
            ->get(self::BASE_URL."/standard/v1/payments/{$reference}");

        if ($response->successful()) {
            $status = $response->json('data.transaction.status');

            return ['success' => $status === 'TS' || $status === 'SUCCESS', 'message' => $status ?? 'unknown', 'status' => $status];
        }

        return ['success' => false, 'message' => $response->json('status.message') ?? $response->body()];
    }

    public function refund(Integration $integration, string $reference, ?int $amountMinor = null, ?string $currency = null): array
    {
        $token = $this->token($integration);

        if (! $token) {
            return ['success' => false, 'message' => 'Could not authenticate with Airtel Money.'];
        }

        $response = Http::withToken($token)
            ->withHeaders($this->countryCurrencyHeaders($integration))
            ->post(self::BASE_URL.'/standard/v1/payments/refund', [
                'transaction' => ['airtel_money_id' => $reference],
            ]);

        if ($response->successful() && $response->json('status.success')) {
            return ['success' => true, 'message' => 'Refunded.'];
        }

        return ['success' => false, 'message' => $response->json('status.message') ?? $response->body()];
    }

    public function test(Integration $integration): array
    {
        return $this->token($integration)
            ? ['success' => true, 'message' => 'Airtel Money credentials verified.']
            : ['success' => false, 'message' => 'Airtel Money rejected these credentials.'];
    }

    protected function token(Integration $integration): ?string
    {
        return $this->fetchToken(
            self::BASE_URL.'/auth/oauth2/token',
            [
                'client_id' => $integration->credential('api_key'),
                'client_secret' => $integration->credential('api_secret'),
                'grant_type' => 'client_credentials',
            ],
            asForm: false,
        );
    }

    protected function countryCurrencyHeaders(Integration $integration): array
    {
        return [
            'X-Country' => $integration->credential('username') ?: 'TZ',
            'X-Currency' => $integration->credential('password') ?: 'TZS',
            'Content-Type' => 'application/json',
        ];
    }
}
