<?php

namespace App\Modules\Payments\Drivers;

use App\Modules\Integrations\Contracts\IntegrationTester;
use App\Modules\Integrations\Models\Integration;
use App\Modules\Payments\Contracts\PaymentGatewayInterface;
use App\Modules\Payments\Drivers\Concerns\FetchesOAuth2Token;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * AzamPay (https://azampay.co.tz) - a Tanzanian payment aggregator
 * unifying mobile money and bank collection behind one API. Token
 * exchange is on a separate "authenticator" host from the payment
 * host itself. Credential mapping: api_key = Client ID, api_secret =
 * Client Secret, merchant_id = App Name (required by the token
 * exchange alongside the client credentials).
 *
 * Confidence note: AzamPay publishes developer documentation, but it's
 * far less globally referenced than Stripe/PayPal/Flutterwave's - this
 * reflects the general shape of their published checkout flow, not a
 * verified integration against a live account.
 */
class AzamPayDriver implements IntegrationTester, PaymentGatewayInterface
{
    use FetchesOAuth2Token;

    protected function authUrl(Integration $integration): string
    {
        return $integration->mode === 'live'
            ? 'https://authenticator.azampay.co.tz/AppRegistration/GenerateToken'
            : 'https://authenticator-sandbox.azampay.co.tz/AppRegistration/GenerateToken';
    }

    protected function apiUrl(Integration $integration): string
    {
        return $integration->mode === 'live'
            ? 'https://checkout.azampay.co.tz'
            : 'https://sandbox.azampay.co.tz';
    }

    public function charge(Integration $integration, int $amountMinor, string $currency, string $reference, array $meta = []): array
    {
        $token = $this->token($integration);

        if (! $token) {
            return ['success' => false, 'message' => 'Could not authenticate with AzamPay.'];
        }

        $response = Http::withToken($token)->post($this->apiUrl($integration).'/azampay/mno/checkout', [
            'accountNumber' => $meta['phone'] ?? '',
            'amount' => (string) ($amountMinor / 100),
            'currency' => strtoupper($currency),
            'externalId' => $reference,
            'provider' => $meta['network'] ?? 'Airtel',
        ]);

        if ($response->successful() && $response->json('success') !== false) {
            return [
                'success' => true,
                'message' => $response->json('message') ?? 'Payment request sent to customer.',
                'reference' => $response->json('transactionId') ?? $reference,
                'status' => 'pending',
            ];
        }

        return ['success' => false, 'message' => $response->json('message') ?? $response->body()];
    }

    public function verify(Integration $integration, string $reference): array
    {
        $token = $this->token($integration);

        if (! $token) {
            return ['success' => false, 'message' => 'Could not authenticate with AzamPay.'];
        }

        $response = Http::withToken($token)->get($this->apiUrl($integration)."/azampay/transactions/{$reference}");

        if ($response->successful()) {
            $status = $response->json('transactionStatus');

            return ['success' => in_array($status, ['Success', 'SUCCESS', 'completed'], true), 'message' => $status ?? 'unknown', 'status' => $status];
        }

        return ['success' => false, 'message' => $response->json('message') ?? $response->body()];
    }

    public function refund(Integration $integration, string $reference, ?int $amountMinor = null, ?string $currency = null): array
    {
        // AzamPay does not publish a self-service refund endpoint for
        // mobile-money collections - reversals go through their
        // merchant support process, not this API.
        return ['success' => false, 'message' => 'AzamPay does not support refunds via this API - contact AzamPay support to process the refund.'];
    }

    public function test(Integration $integration): array
    {
        return $this->token($integration)
            ? ['success' => true, 'message' => 'AzamPay credentials verified.']
            : ['success' => false, 'message' => 'AzamPay rejected these credentials.'];
    }

    protected function token(Integration $integration): ?string
    {
        return $this->fetchToken(
            $this->authUrl($integration),
            [
                'appName' => $integration->credential('merchant_id') ?: Str::slug(config('app.name')),
                'clientId' => $integration->credential('api_key'),
                'clientSecret' => $integration->credential('api_secret'),
            ],
            tokenField: 'data.accessToken',
            asForm: false,
        );
    }
}
