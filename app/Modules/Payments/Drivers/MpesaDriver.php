<?php

namespace App\Modules\Payments\Drivers;

use App\Modules\Integrations\Contracts\IntegrationTester;
use App\Modules\Integrations\Models\Integration;
use App\Modules\Payments\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Facades\Http;

/**
 * M-Pesa "Lipa Na M-Pesa Online" (STK Push) - the Daraja-style API
 * pattern Safaricom standardized and Vodacom Tanzania's M-Pesa Open
 * API follows the same shape of: OAuth2 client-credentials token, an
 * STK push request that prompts the customer's phone, and a status
 * query. Credential mapping: api_key = Consumer Key, api_secret =
 * Consumer Secret, merchant_id = Business Shortcode, username = Lipa
 * Na M-Pesa Online Passkey.
 *
 * Confidence note: this is the most standardized mobile-money API
 * pattern in the region (Safaricom's Daraja API is extensively
 * publicly documented), but has not been exercised against a live
 * account - verify the base URL (sandbox vs. production differ, see
 * BASE_URL) and callback handling before enabling for a real company.
 */
class MpesaDriver implements IntegrationTester, PaymentGatewayInterface
{
    protected function baseUrl(Integration $integration): string
    {
        return $integration->mode === 'live'
            ? 'https://api.safaricom.co.tz'
            : 'https://sandbox.safaricom.co.tz';
    }

    public function charge(Integration $integration, int $amountMinor, string $currency, string $reference, array $meta = []): array
    {
        $token = $this->token($integration);

        if (! $token) {
            return ['success' => false, 'message' => 'Could not authenticate with M-Pesa.'];
        }

        $shortcode = $integration->credential('merchant_id');
        $passkey = $integration->credential('username');
        $timestamp = now()->format('YmdHis');
        $password = base64_encode($shortcode.$passkey.$timestamp);

        $response = Http::withToken($token)->post($this->baseUrl($integration).'/mpesa/stkpush/v1/processrequest', [
            'BusinessShortCode' => $shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => (int) round($amountMinor / 100),
            'PartyA' => $meta['phone'] ?? '',
            'PartyB' => $shortcode,
            'PhoneNumber' => $meta['phone'] ?? '',
            'CallBackURL' => $integration->webhook_url ?? url('/'),
            'AccountReference' => $reference,
            'TransactionDesc' => $meta['description'] ?? 'Payment',
        ]);

        if ($response->successful() && $response->json('ResponseCode') === '0') {
            return [
                'success' => true,
                'message' => $response->json('CustomerMessage') ?? 'STK push sent to customer.',
                'reference' => $response->json('CheckoutRequestID'),
                'status' => 'pending',
            ];
        }

        return ['success' => false, 'message' => $response->json('errorMessage') ?? $response->body()];
    }

    public function verify(Integration $integration, string $reference): array
    {
        $token = $this->token($integration);

        if (! $token) {
            return ['success' => false, 'message' => 'Could not authenticate with M-Pesa.'];
        }

        $shortcode = $integration->credential('merchant_id');
        $passkey = $integration->credential('username');
        $timestamp = now()->format('YmdHis');

        $response = Http::withToken($token)->post($this->baseUrl($integration).'/mpesa/stkpushquery/v1/query', [
            'BusinessShortCode' => $shortcode,
            'Password' => base64_encode($shortcode.$passkey.$timestamp),
            'Timestamp' => $timestamp,
            'CheckoutRequestID' => $reference,
        ]);

        if ($response->successful()) {
            $resultCode = $response->json('ResultCode');

            return ['success' => $resultCode === '0' || $resultCode === 0, 'message' => $response->json('ResultDesc') ?? 'unknown', 'status' => (string) $resultCode];
        }

        return ['success' => false, 'message' => $response->json('errorMessage') ?? $response->body()];
    }

    public function refund(Integration $integration, string $reference, ?int $amountMinor = null, ?string $currency = null): array
    {
        $token = $this->token($integration);

        if (! $token) {
            return ['success' => false, 'message' => 'Could not authenticate with M-Pesa.'];
        }

        // Daraja's Transaction Reversal API - $reference here is the
        // original M-Pesa transaction ID (from a completed payment's
        // callback), not the CheckoutRequestID used to initiate it.
        $response = Http::withToken($token)->post($this->baseUrl($integration).'/mpesa/reversal/v1/request', array_filter([
            'Initiator' => $integration->credential('username'),
            'TransactionID' => $reference,
            'Amount' => $amountMinor !== null ? (int) round($amountMinor / 100) : null,
            'ReceiverParty' => $integration->credential('merchant_id'),
            'RecieverIdentifierType' => '11',
            'ResultURL' => $integration->webhook_url ?? url('/'),
            'QueueTimeOutURL' => $integration->webhook_url ?? url('/'),
            'Remarks' => 'Refund',
        ]));

        if ($response->successful() && $response->json('ResponseCode') === '0') {
            return ['success' => true, 'message' => 'Reversal request accepted.'];
        }

        return ['success' => false, 'message' => $response->json('errorMessage') ?? $response->body()];
    }

    public function test(Integration $integration): array
    {
        return $this->token($integration)
            ? ['success' => true, 'message' => 'M-Pesa credentials verified.']
            : ['success' => false, 'message' => 'M-Pesa rejected these credentials.'];
    }

    protected function token(Integration $integration): ?string
    {
        // Daraja's token endpoint is a GET with the grant type as a
        // query param and Basic Auth - no request body - unlike most
        // other gateways in this catalog.
        $response = Http::withBasicAuth((string) $integration->credential('api_key'), (string) $integration->credential('api_secret'))
            ->get($this->baseUrl($integration).'/oauth/v1/generate', ['grant_type' => 'client_credentials']);

        return $response->successful() ? $response->json('access_token') : null;
    }
}
