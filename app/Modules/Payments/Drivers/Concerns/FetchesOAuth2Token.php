<?php

namespace App\Modules\Payments\Drivers\Concerns;

use Illuminate\Support\Facades\Http;

/**
 * Shared by every driver whose gateway issues a short-lived Bearer
 * token via an OAuth2 client-credentials-style exchange (M-Pesa,
 * Airtel Money, AzamPay, Tigo Pesa, HaloPesa, NMB, CRDB, NBC) - the
 * exact same "POST credentials, read access_token back" step repeats
 * across all of them, only the URL/payload/field name vary.
 */
trait FetchesOAuth2Token
{
    protected function fetchToken(string $url, array $payload, string $tokenField = 'access_token', bool $asForm = true): ?string
    {
        $request = $asForm ? Http::asForm() : Http::asJson();
        $response = $request->post($url, $payload);

        return $response->successful() ? $response->json($tokenField) : null;
    }

    protected function fetchBasicAuthToken(string $url, string $username, string $password, array $payload = [], string $tokenField = 'access_token'): ?string
    {
        $response = Http::asForm()->withBasicAuth($username, $password)->post($url, $payload);

        return $response->successful() ? $response->json($tokenField) : null;
    }
}
