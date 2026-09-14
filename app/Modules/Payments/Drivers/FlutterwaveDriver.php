<?php

namespace App\Modules\Payments\Drivers;

use App\Modules\Integrations\Contracts\IntegrationTester;
use App\Modules\Integrations\Models\Integration;
use App\Modules\Payments\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Facades\Http;

/**
 * Flutterwave's v3 API (https://developer.flutterwave.com/docs) - a
 * single secret key as a Bearer token, JSON bodies. `tx_ref` (this
 * driver's $reference) doubles as Flutterwave's own idempotency key -
 * a duplicate tx_ref is rejected rather than double-charged.
 *
 * Credential mapping: api_secret = Secret Key (required), api_key =
 * Public Key (optional, unused server-side, kept for a future inline
 * frontend checkout).
 */
class FlutterwaveDriver implements IntegrationTester, PaymentGatewayInterface
{
    public function charge(Integration $integration, int $amountMinor, string $currency, string $reference, array $meta = []): array
    {
        $response = Http::withToken($integration->credential('api_secret'))
            ->post('https://api.flutterwave.com/v3/payments', [
                'tx_ref' => $reference,
                'amount' => $amountMinor / 100,
                'currency' => strtoupper($currency),
                'redirect_url' => $meta['success_url'] ?? url('/'),
                'customer' => ['email' => $meta['email'] ?? 'customer@example.com'],
            ]);

        if ($response->successful() && $response->json('status') === 'success') {
            return [
                'success' => true,
                'message' => 'Payment link created.',
                'reference' => $reference,
                'redirect_url' => $response->json('data.link'),
                'status' => 'pending',
            ];
        }

        return ['success' => false, 'message' => $response->json('message') ?? $response->body()];
    }

    public function verify(Integration $integration, string $reference): array
    {
        $response = Http::withToken($integration->credential('api_secret'))
            ->get('https://api.flutterwave.com/v3/transactions/verify_by_reference', ['tx_ref' => $reference]);

        if ($response->successful()) {
            $status = $response->json('data.status');

            return ['success' => $status === 'successful', 'message' => $status ?? 'unknown', 'status' => $status];
        }

        return ['success' => false, 'message' => $response->json('message') ?? $response->body()];
    }

    public function refund(Integration $integration, string $reference, ?int $amountMinor = null, ?string $currency = null): array
    {
        // $reference here is Flutterwave's own numeric transaction id
        // (verify()'s data.id, read in real usage), not the tx_ref.
        $response = Http::withToken($integration->credential('api_secret'))
            ->post("https://api.flutterwave.com/v3/transactions/{$reference}/refund", array_filter([
                'amount' => $amountMinor !== null ? $amountMinor / 100 : null,
            ]));

        if ($response->successful()) {
            return ['success' => true, 'message' => 'Refunded.'];
        }

        return ['success' => false, 'message' => $response->json('message') ?? $response->body()];
    }

    public function test(Integration $integration): array
    {
        $response = Http::withToken($integration->credential('api_secret'))->get('https://api.flutterwave.com/v3/balances');

        if ($response->successful()) {
            return ['success' => true, 'message' => 'Flutterwave account credentials verified.'];
        }

        return ['success' => false, 'message' => $response->json('message') ?? 'Flutterwave rejected these credentials.'];
    }
}
