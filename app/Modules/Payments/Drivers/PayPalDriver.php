<?php

namespace App\Modules\Payments\Drivers;

use App\Modules\Integrations\Contracts\IntegrationTester;
use App\Modules\Integrations\Models\Integration;
use App\Modules\Payments\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Facades\Http;

/**
 * PayPal's REST API v2 (https://developer.paypal.com/api/rest/) - an
 * OAuth2 client-credentials exchange (Basic Auth with your Client
 * ID/Secret) yields a short-lived Bearer token, then the Orders API
 * creates/captures/refunds against it. Sandbox and live use different
 * hosts, selected by the integration's own `mode` column (already on
 * every Integration since Module 7 - sandbox/live).
 *
 * Credential mapping: api_key = Client ID, api_secret = Client Secret.
 */
class PayPalDriver implements IntegrationTester, PaymentGatewayInterface
{
    public function charge(Integration $integration, int $amountMinor, string $currency, string $reference, array $meta = []): array
    {
        $token = $this->accessToken($integration);

        if (! $token) {
            return ['success' => false, 'message' => 'Could not authenticate with PayPal.'];
        }

        $response = Http::withToken($token)
            ->withHeaders(['PayPal-Request-Id' => $reference])
            ->post($this->baseUrl($integration).'/v2/checkout/orders', [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => $reference,
                    'amount' => [
                        'currency_code' => strtoupper($currency),
                        'value' => $this->toDecimal($amountMinor),
                    ],
                ]],
                'application_context' => [
                    'return_url' => $meta['success_url'] ?? url('/'),
                    'cancel_url' => $meta['cancel_url'] ?? url('/'),
                ],
            ]);

        if ($response->successful()) {
            $approveUrl = collect($response->json('links'))->firstWhere('rel', 'approve')['href'] ?? null;

            return [
                'success' => true,
                'message' => 'Order created.',
                'reference' => $response->json('id'),
                'redirect_url' => $approveUrl,
                'status' => $response->json('status'),
            ];
        }

        return ['success' => false, 'message' => $response->json('message') ?? $response->body()];
    }

    public function verify(Integration $integration, string $reference): array
    {
        $token = $this->accessToken($integration);

        if (! $token) {
            return ['success' => false, 'message' => 'Could not authenticate with PayPal.'];
        }

        $response = Http::withToken($token)->get($this->baseUrl($integration)."/v2/checkout/orders/{$reference}");

        if ($response->successful()) {
            $status = $response->json('status');

            return ['success' => $status === 'COMPLETED', 'message' => $status ?? 'unknown', 'status' => $status];
        }

        return ['success' => false, 'message' => $response->json('message') ?? $response->body()];
    }

    public function refund(Integration $integration, string $reference, ?int $amountMinor = null, ?string $currency = null): array
    {
        // $reference here is the capture id (an order's
        // purchase_units[].payments.captures[].id, read via verify()
        // in real usage), not the order id itself.
        $token = $this->accessToken($integration);

        if (! $token) {
            return ['success' => false, 'message' => 'Could not authenticate with PayPal.'];
        }

        $payload = [];

        if ($amountMinor !== null && $currency !== null) {
            $payload['amount'] = ['value' => $this->toDecimal($amountMinor), 'currency_code' => strtoupper($currency)];
        }

        $response = Http::withToken($token)->post($this->baseUrl($integration)."/v2/payments/captures/{$reference}/refund", $payload);

        if ($response->successful()) {
            return ['success' => true, 'message' => 'Refunded.'];
        }

        return ['success' => false, 'message' => $response->json('message') ?? $response->body()];
    }

    public function test(Integration $integration): array
    {
        return $this->accessToken($integration)
            ? ['success' => true, 'message' => 'PayPal client credentials verified.']
            : ['success' => false, 'message' => 'PayPal rejected these credentials.'];
    }

    protected function accessToken(Integration $integration): ?string
    {
        $response = Http::asForm()
            ->withBasicAuth($integration->credential('api_key'), $integration->credential('api_secret'))
            ->post($this->baseUrl($integration).'/v1/oauth2/token', ['grant_type' => 'client_credentials']);

        return $response->successful() ? $response->json('access_token') : null;
    }

    protected function baseUrl(Integration $integration): string
    {
        return $integration->mode === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';
    }

    protected function toDecimal(int $amountMinor): string
    {
        return number_format($amountMinor / 100, 2, '.', '');
    }
}
