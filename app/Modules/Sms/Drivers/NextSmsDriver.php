<?php

namespace App\Modules\Sms\Drivers;

use App\Modules\Integrations\Contracts\IntegrationTester;
use App\Modules\Integrations\Models\Integration;
use App\Modules\Sms\Contracts\SmsProviderInterface;
use Illuminate\Support\Facades\Http;

/**
 * NextSMS (Beula Solutions, Tanzania) - HTTP Basic Auth using your
 * account username/password, JSON body.
 *
 * Confidence note: this is the least publicly documented provider in
 * the catalog - the endpoint/payload shape below (including the
 * balance-check endpoint test() uses) is a best-effort reflection of
 * NextSMS's API and has NOT been verified against a live account.
 * Confirm the exact endpoints/payloads against NextSMS's current
 * documentation before relying on this for a real company - if the
 * balance endpoint turns out to be wrong, only test() needs correcting
 * here, send() is unaffected.
 */
class NextSmsDriver implements IntegrationTester, SmsProviderInterface
{
    protected const BASE_URL = 'https://messaging-service.co.tz/api/sms/v1';

    public function send(Integration $integration, string $to, string $message): array
    {
        $response = Http::withBasicAuth($integration->credential('api_key'), $integration->credential('api_secret'))
            ->post(self::BASE_URL.'/text/single', [
                'from' => $integration->credential('merchant_id'),
                'to' => $to,
                'text' => $message,
            ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'message' => 'Sent.',
                'message_id' => $response->json('messages.0.messageId'),
            ];
        }

        return ['success' => false, 'message' => $response->body()];
    }

    public function test(Integration $integration): array
    {
        $response = Http::withBasicAuth($integration->credential('api_key'), $integration->credential('api_secret'))
            ->get(self::BASE_URL.'/balance');

        if ($response->status() === 401 || $response->status() === 403) {
            return ['success' => false, 'message' => 'NextSMS rejected these credentials.'];
        }

        if ($response->successful()) {
            return ['success' => true, 'message' => 'NextSMS credentials verified.'];
        }

        return ['success' => false, 'message' => $response->body() ?: 'NextSMS did not respond as expected.'];
    }
}
