<?php

namespace App\Modules\Payments\Drivers;

use App\Modules\Integrations\Contracts\IntegrationTester;
use App\Modules\Integrations\Models\Integration;
use App\Modules\Payments\Contracts\PaymentGatewayInterface;
use App\Modules\Payments\Drivers\Concerns\FetchesOAuth2Token;
use Illuminate\Support\Facades\Http;

/**
 * Tigo Pesa (Tanzania, now marketed as Mixx by Yas) - the lowest
 * confidence driver in this catalog. Unlike M-Pesa/Airtel Money/
 * AzamPay, Tigo doesn't publish a well-known direct merchant API the
 * way Safaricom's Daraja or Airtel Africa's Open API portal do; in
 * practice, merchants usually reach Tigo Pesa through an aggregator
 * (Selcom, ClickPesa, DPO, etc.) rather than integrating directly.
 *
 * Implemented here against the OAuth2-token-then-collection-request
 * pattern nearly every East African mobile money API converges on
 * (see MpesaDriver, AirtelMoneyDriver) - the request/response handling
 * is real and complete, but the base URL is NOT a confirmed real
 * hostname, unlike every other driver in this module. Rather than
 * guess at one, it's read from the credential's `password` field
 * (relabeled "Base URL Override" for this provider) so a real endpoint
 * can be dropped in the moment it's known, with zero code changes.
 *
 * Credential mapping: api_key = Client ID/Username, api_secret =
 * Client Secret/Password, merchant_id = Merchant/Shortcode ID,
 * password = Base URL Override (required - see above).
 */
class TigoPesaDriver implements IntegrationTester, PaymentGatewayInterface
{
    use FetchesOAuth2Token;

    public function charge(Integration $integration, int $amountMinor, string $currency, string $reference, array $meta = []): array
    {
        $baseUrl = $this->baseUrl($integration);

        if (! $baseUrl) {
            return $this->missingBaseUrl();
        }

        $token = $this->token($integration, $baseUrl);

        if (! $token) {
            return ['success' => false, 'message' => 'Could not authenticate with Tigo Pesa.'];
        }

        $response = Http::withToken($token)->post($baseUrl.'/merchant/v1/collections', [
            'MerchantID' => $integration->credential('merchant_id'),
            'ReferenceID' => $reference,
            'MSISDN' => $meta['phone'] ?? '',
            'Amount' => (int) round($amountMinor / 100),
            'Currency' => strtoupper($currency),
            'CallbackURL' => $integration->webhook_url ?? url('/'),
        ]);

        if ($response->successful() && in_array($response->json('ResponseCode'), ['0', 0, 'BILLER-30-0000-S'], true)) {
            return [
                'success' => true,
                'message' => $response->json('ResponseDescription') ?? 'Payment request sent to customer.',
                'reference' => $response->json('TransactionID') ?? $reference,
                'status' => 'pending',
            ];
        }

        return ['success' => false, 'message' => $response->json('ResponseDescription') ?? $response->body()];
    }

    public function verify(Integration $integration, string $reference): array
    {
        $baseUrl = $this->baseUrl($integration);

        if (! $baseUrl) {
            return $this->missingBaseUrl();
        }

        $token = $this->token($integration, $baseUrl);

        if (! $token) {
            return ['success' => false, 'message' => 'Could not authenticate with Tigo Pesa.'];
        }

        $response = Http::withToken($token)->get($baseUrl."/merchant/v1/collections/{$reference}");

        if ($response->successful()) {
            $status = $response->json('Status');

            return ['success' => in_array($status, ['SUCCESS', 'Success', 'completed'], true), 'message' => $status ?? 'unknown', 'status' => $status];
        }

        return ['success' => false, 'message' => $response->json('ResponseDescription') ?? $response->body()];
    }

    public function refund(Integration $integration, string $reference, ?int $amountMinor = null, ?string $currency = null): array
    {
        return ['success' => false, 'message' => 'Tigo Pesa does not have a confirmed refund API in this integration - process the refund through your Tigo Pesa merchant relationship.'];
    }

    public function test(Integration $integration): array
    {
        $baseUrl = $this->baseUrl($integration);

        if (! $baseUrl) {
            return $this->missingBaseUrl();
        }

        return $this->token($integration, $baseUrl)
            ? ['success' => true, 'message' => 'Tigo Pesa credentials verified.']
            : ['success' => false, 'message' => 'Tigo Pesa rejected these credentials.'];
    }

    protected function baseUrl(Integration $integration): ?string
    {
        return $integration->credential('password') ?: null;
    }

    protected function token(Integration $integration, string $baseUrl): ?string
    {
        return $this->fetchToken(
            $baseUrl.'/oauth/token',
            [
                'client_id' => $integration->credential('api_key'),
                'client_secret' => $integration->credential('api_secret'),
                'grant_type' => 'client_credentials',
            ],
        );
    }

    protected function missingBaseUrl(): array
    {
        return [
            'success' => false,
            'message' => 'Tigo Pesa needs a Base URL Override (in the Password field) before it can be used - this provider has no confirmed public API host built into the app.',
        ];
    }
}
