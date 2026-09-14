<?php

namespace App\Modules\Payments\Drivers;

use App\Modules\Integrations\Contracts\IntegrationTester;
use App\Modules\Integrations\Models\Integration;
use App\Modules\Payments\Contracts\PaymentGatewayInterface;
use App\Modules\Payments\Drivers\Concerns\FetchesOAuth2Token;
use Illuminate\Support\Facades\Http;

/**
 * HaloPesa (Halotel, Tanzania) - same confidence situation as
 * TigoPesaDriver and the same fix: no confirmed public direct-merchant
 * API host is built into this app, so the base URL is read from the
 * credential's `password` field ("Base URL Override") rather than
 * guessed at. The OAuth2-token-then-collection-request pattern is the
 * same shape every other East African mobile money driver in this
 * module uses (see MpesaDriver, AirtelMoneyDriver) - real, complete
 * request/response handling, just pointed at a host to be confirmed.
 *
 * Credential mapping: api_key = Client ID/Username, api_secret =
 * Client Secret/Password, merchant_id = Merchant/Shortcode ID,
 * password = Base URL Override (required - see above).
 */
class HaloPesaDriver implements IntegrationTester, PaymentGatewayInterface
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
            return ['success' => false, 'message' => 'Could not authenticate with HaloPesa.'];
        }

        $response = Http::withToken($token)->post($baseUrl.'/merchant/v1/collections', [
            'MerchantID' => $integration->credential('merchant_id'),
            'ReferenceID' => $reference,
            'MSISDN' => $meta['phone'] ?? '',
            'Amount' => (int) round($amountMinor / 100),
            'Currency' => strtoupper($currency),
            'CallbackURL' => $integration->webhook_url ?? url('/'),
        ]);

        if ($response->successful() && in_array($response->json('ResponseCode'), ['0', 0], true)) {
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
            return ['success' => false, 'message' => 'Could not authenticate with HaloPesa.'];
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
        return ['success' => false, 'message' => 'HaloPesa does not have a confirmed refund API in this integration - process the refund through your HaloPesa merchant relationship.'];
    }

    public function test(Integration $integration): array
    {
        $baseUrl = $this->baseUrl($integration);

        if (! $baseUrl) {
            return $this->missingBaseUrl();
        }

        return $this->token($integration, $baseUrl)
            ? ['success' => true, 'message' => 'HaloPesa credentials verified.']
            : ['success' => false, 'message' => 'HaloPesa rejected these credentials.'];
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
            'message' => 'HaloPesa needs a Base URL Override (in the Password field) before it can be used - this provider has no confirmed public API host built into the app.',
        ];
    }
}
