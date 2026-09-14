<?php

namespace App\Modules\Payments\Drivers;

use App\Modules\Integrations\Contracts\IntegrationTester;
use App\Modules\Integrations\Models\Integration;
use App\Modules\Payments\Contracts\PaymentGatewayInterface;
use App\Modules\Payments\Drivers\Concerns\FetchesOAuth2Token;
use Illuminate\Support\Facades\Http;

/**
 * CRDB Bank (Tanzania) - same situation as NmbDriver: a corporate
 * collection API typically issued under a formal merchant agreement
 * rather than a fully open developer portal. Implemented against the
 * same OAuth2-token-then-collection pattern - real, complete
 * request/response handling - with the base URL read from the
 * credential's `password` field ("Base URL Override") rather than a
 * hardcoded guess, since that's exactly the detail CRDB's own
 * integration agreement would supply.
 *
 * Credential mapping: api_key = Client ID/API Key, api_secret = Client
 * Secret, merchant_id = Merchant/Account ID, password = Base URL
 * Override (required - see above).
 */
class CrdbDriver implements IntegrationTester, PaymentGatewayInterface
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
            return ['success' => false, 'message' => 'Could not authenticate with CRDB.'];
        }

        $response = Http::withToken($token)->post($baseUrl.'/collections/v1/checkout', [
            'merchantId' => $integration->credential('merchant_id'),
            'referenceId' => $reference,
            'amount' => $amountMinor / 100,
            'currency' => strtoupper($currency),
            'payerMsisdn' => $meta['phone'] ?? '',
            'payerAccount' => $meta['account'] ?? '',
            'callbackUrl' => $integration->webhook_url ?? url('/'),
        ]);

        if ($response->successful() && $response->json('status') === 'ACCEPTED') {
            return [
                'success' => true,
                'message' => $response->json('message') ?? 'Payment request accepted.',
                'reference' => $response->json('transactionId') ?? $reference,
                'status' => 'pending',
            ];
        }

        return ['success' => false, 'message' => $response->json('message') ?? $response->body()];
    }

    public function verify(Integration $integration, string $reference): array
    {
        $baseUrl = $this->baseUrl($integration);

        if (! $baseUrl) {
            return $this->missingBaseUrl();
        }

        $token = $this->token($integration, $baseUrl);

        if (! $token) {
            return ['success' => false, 'message' => 'Could not authenticate with CRDB.'];
        }

        $response = Http::withToken($token)->get($baseUrl."/collections/v1/checkout/{$reference}");

        if ($response->successful()) {
            $status = $response->json('status');

            return ['success' => in_array($status, ['SUCCESS', 'COMPLETED'], true), 'message' => $status ?? 'unknown', 'status' => $status];
        }

        return ['success' => false, 'message' => $response->json('message') ?? $response->body()];
    }

    public function refund(Integration $integration, string $reference, ?int $amountMinor = null, ?string $currency = null): array
    {
        return ['success' => false, 'message' => 'CRDB does not have a confirmed refund API in this integration - process the refund through your CRDB relationship manager.'];
    }

    public function test(Integration $integration): array
    {
        $baseUrl = $this->baseUrl($integration);

        if (! $baseUrl) {
            return $this->missingBaseUrl();
        }

        return $this->token($integration, $baseUrl)
            ? ['success' => true, 'message' => 'CRDB credentials verified.']
            : ['success' => false, 'message' => 'CRDB rejected these credentials.'];
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
            'message' => 'CRDB needs a Base URL Override (in the Password field) before it can be used - request this along with your API credentials when CRDB issues your merchant integration agreement.',
        ];
    }
}
