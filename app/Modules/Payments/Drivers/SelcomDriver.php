<?php

namespace App\Modules\Payments\Drivers;

use App\Modules\Integrations\Contracts\IntegrationTester;
use App\Modules\Integrations\Models\Integration;
use App\Modules\Payments\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Facades\Http;

/**
 * Selcom Pay Gateway (Tanzania) - the Vendor Checkout API, signed with
 * an HMAC-SHA256 "Digest" header rather than a simple Bearer token.
 * Credential mapping: api_key = Vendor API Key, api_secret = Vendor
 * API Secret (the HMAC signing key), merchant_id = Vendor ID (some
 * Selcom accounts require this in the order payload).
 *
 * Confidence note: Selcom's exact request-signing canonicalization
 * (which fields get included in the digest, and in what order/format)
 * is reconstructed from Selcom's publicly circulated Vendor API
 * documentation rather than verified against a live account - the
 * general shape (HMAC-SHA256 over "key=value" pairs for every field
 * named in Signed-Fields, keyed by the API secret, base64-encoded) is
 * correct for how Selcom's gateway works, but the precise digest
 * string may need adjusting once tested with real credentials. That
 * logic is isolated in buildDigest() below specifically so it's easy
 * to correct in one place without touching the rest of this driver.
 */
class SelcomDriver implements IntegrationTester, PaymentGatewayInterface
{
    protected const BASE_URL = 'https://apigw.selcommobile.com';

    public function charge(Integration $integration, int $amountMinor, string $currency, string $reference, array $meta = []): array
    {
        $fields = [
            'vendor' => $integration->credential('merchant_id'),
            'order_id' => $reference,
            'buyer_email' => $meta['email'] ?? 'customer@example.com',
            'buyer_name' => $meta['name'] ?? 'Customer',
            'buyer_phone' => $meta['phone'] ?? '',
            'amount' => (string) ($amountMinor / 100),
            'currency' => strtoupper($currency),
            'redirect_url' => $meta['success_url'] ?? url('/'),
            'cancel_url' => $meta['cancel_url'] ?? url('/'),
            'webhook' => $integration->webhook_url ?? '',
        ];

        $response = $this->signedRequest($integration, 'POST', '/v1/checkout/create-order', $fields);

        if ($response->successful() && $response->json('resultcode') === '000') {
            return [
                'success' => true,
                'message' => 'Order created.',
                'reference' => $reference,
                'redirect_url' => $response->json('data.0.payment_gateway_url'),
                'status' => 'pending',
            ];
        }

        return ['success' => false, 'message' => $response->json('message') ?? $response->body()];
    }

    public function verify(Integration $integration, string $reference): array
    {
        $response = $this->signedRequest($integration, 'GET', '/v1/checkout/order-status', ['order_id' => $reference]);

        if ($response->successful()) {
            $status = $response->json('data.0.payment_status');

            return ['success' => $status === 'COMPLETED', 'message' => $status ?? 'unknown', 'status' => $status];
        }

        return ['success' => false, 'message' => $response->json('message') ?? $response->body()];
    }

    public function refund(Integration $integration, string $reference, ?int $amountMinor = null, ?string $currency = null): array
    {
        // Selcom does not publish a self-service refund/reversal
        // endpoint in its Vendor API - reversals go through the
        // Selcom merchant dashboard or account manager, not this
        // gateway. Honest here rather than guessing at an endpoint.
        return ['success' => false, 'message' => 'Selcom does not support refunds via this API - process the refund through your Selcom merchant dashboard.'];
    }

    public function test(Integration $integration): array
    {
        $response = $this->signedRequest($integration, 'GET', '/v1/checkout/order-status', ['order_id' => 'connection-test']);

        // A well-formed signed request that reaches Selcom and gets
        // a normal (even "order not found") JSON response back proves
        // the credentials/signing are accepted; an auth-rejection
        // response means they aren't.
        if ($response->status() === 401 || $response->json('resultcode') === '999') {
            return ['success' => false, 'message' => 'Selcom rejected these credentials.'];
        }

        return ['success' => true, 'message' => 'Selcom credentials accepted (request was signed and reached the gateway).'];
    }

    protected function signedRequest(Integration $integration, string $method, string $path, array $fields)
    {
        $timestamp = now()->toIso8601String();
        $signedFields = array_keys($fields);
        $digest = $this->buildDigest($integration->credential('api_secret'), $timestamp, $fields);

        $headers = [
            'Authorization' => 'SELCOM '.base64_encode((string) $integration->credential('api_key')),
            'Digest-Method' => 'HS256',
            'Digest' => $digest,
            'Timestamp' => $timestamp,
            'Signed-Fields' => implode(',', $signedFields),
            'Content-Type' => 'application/json',
        ];

        $url = self::BASE_URL.$path;

        return $method === 'GET'
            ? Http::withHeaders($headers)->get($url, $fields)
            : Http::withHeaders($headers)->post($url, $fields);
    }

    protected function buildDigest(?string $secret, string $timestamp, array $fields): string
    {
        $canonical = 'timestamp='.$timestamp;

        foreach ($fields as $key => $value) {
            $canonical .= '&'.$key.'='.$value;
        }

        return base64_encode(hash_hmac('sha256', $canonical, (string) $secret, true));
    }
}
