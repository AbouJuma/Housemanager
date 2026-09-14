<?php

namespace App\Modules\Payments\Drivers;

use App\Modules\Integrations\Contracts\IntegrationTester;
use App\Modules\Integrations\Models\Integration;
use App\Modules\Payments\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Facades\Http;

/**
 * Stripe's REST API (https://docs.stripe.com/api) - a single secret
 * key as a Bearer token, form-encoded (not JSON) request bodies. The
 * highest-confidence driver in this catalog, alongside PayPal and
 * Flutterwave - built against a stable, extensively-referenced public
 * API rather than reconstructed from a regional/proprietary pattern
 * the way the other nine payment drivers are (see e.g.
 * App\Modules\Payments\Drivers\SelcomDriver's docblock).
 *
 * charge() creates a Checkout Session (a hosted, Stripe-side payment
 * page) rather than attempting a fully server-side charge - there's no
 * saved payment method or Stripe.js integration anywhere in this app
 * yet, and a Checkout Session is the correct, real way to accept a
 * card payment without one. A future Tenant Portal (Phase E) would
 * redirect to `redirect_url`, then this app finds out the outcome via
 * verify() or a webhook.
 *
 * Credential mapping: api_secret = Secret Key (sk_test_.../sk_live_...,
 * required), api_key = Publishable Key (optional, unused server-side,
 * kept for a future frontend integration).
 */
class StripeDriver implements IntegrationTester, PaymentGatewayInterface
{
    public function charge(Integration $integration, int $amountMinor, string $currency, string $reference, array $meta = []): array
    {
        $response = Http::asForm()
            ->withToken($this->secretKey($integration))
            ->withHeaders(['Idempotency-Key' => $reference])
            ->post('https://api.stripe.com/v1/checkout/sessions', [
                'mode' => 'payment',
                'client_reference_id' => $reference,
                'success_url' => $meta['success_url'] ?? url('/'),
                'cancel_url' => $meta['cancel_url'] ?? url('/'),
                'line_items' => [[
                    'quantity' => 1,
                    'price_data' => [
                        'currency' => strtolower($currency),
                        'unit_amount' => $amountMinor,
                        'product_data' => ['name' => $meta['description'] ?? 'Payment'],
                    ],
                ]],
            ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'message' => 'Checkout session created.',
                'reference' => $response->json('id'),
                'redirect_url' => $response->json('url'),
                'status' => 'pending',
            ];
        }

        return ['success' => false, 'message' => $response->json('error.message') ?? $response->body()];
    }

    public function verify(Integration $integration, string $reference): array
    {
        $response = Http::withToken($this->secretKey($integration))
            ->get("https://api.stripe.com/v1/checkout/sessions/{$reference}");

        if ($response->successful()) {
            $status = $response->json('payment_status');

            return ['success' => $status === 'paid', 'message' => $status ?? 'unknown', 'status' => $status];
        }

        return ['success' => false, 'message' => $response->json('error.message') ?? $response->body()];
    }

    public function refund(Integration $integration, string $reference, ?int $amountMinor = null, ?string $currency = null): array
    {
        // $reference here is the PaymentIntent id (Checkout Session's
        // payment_intent field, read via verify() in real usage), not
        // the Checkout Session id itself.
        $response = Http::asForm()
            ->withToken($this->secretKey($integration))
            ->post('https://api.stripe.com/v1/refunds', array_filter([
                'payment_intent' => $reference,
                'amount' => $amountMinor,
            ]));

        if ($response->successful()) {
            return ['success' => true, 'message' => 'Refunded.'];
        }

        return ['success' => false, 'message' => $response->json('error.message') ?? $response->body()];
    }

    public function test(Integration $integration): array
    {
        $response = Http::withToken($this->secretKey($integration))->get('https://api.stripe.com/v1/balance');

        if ($response->successful()) {
            return ['success' => true, 'message' => 'Stripe account credentials verified.'];
        }

        return ['success' => false, 'message' => $response->json('error.message') ?? 'Stripe rejected these credentials.'];
    }

    protected function secretKey(Integration $integration): ?string
    {
        return $integration->credential('api_secret') ?: $integration->credential('api_key');
    }
}
