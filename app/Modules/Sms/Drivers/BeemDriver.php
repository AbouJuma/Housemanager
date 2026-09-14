<?php

namespace App\Modules\Sms\Drivers;

use App\Modules\Integrations\Contracts\IntegrationTester;
use App\Modules\Integrations\Models\Integration;
use App\Modules\Sms\Contracts\SmsProviderInterface;
use Illuminate\Support\Facades\Http;

/**
 * Beem Africa's SMS API (https://beem.africa, docs at
 * https://docs.beem.africa) - HTTP Basic Auth using your API key as the
 * username and secret key as the password, JSON body. Credential
 * mapping: api_key = API Key, api_secret = Secret Key, merchant_id =
 * Source Address (Sender ID).
 *
 * Confidence note: unlike Twilio/Infobip/ClickSend (major platforms
 * with long-stable, extensively-referenced REST APIs), this reflects
 * Beem's publicly documented API to the best of available knowledge but
 * has not been exercised against a live Beem account - verify against
 * a real sandbox credential before enabling it for a real company.
 */
class BeemDriver implements IntegrationTester, SmsProviderInterface
{
    public function send(Integration $integration, string $to, string $message): array
    {
        $response = Http::withBasicAuth($integration->credential('api_key'), $integration->credential('api_secret'))
            ->post('https://apisms.beem.africa/v1/send', [
                'source_addr' => $integration->credential('merchant_id'),
                'encoding' => 0,
                'message' => $message,
                'recipients' => [
                    ['recipient_id' => 1, 'dest_addr' => $to],
                ],
            ]);

        if ($response->successful() && $response->json('successful') !== false) {
            return [
                'success' => true,
                'message' => 'Sent.',
                'message_id' => $response->json('request_id'),
            ];
        }

        return ['success' => false, 'message' => $response->json('message') ?? $response->body()];
    }

    public function test(Integration $integration): array
    {
        $response = Http::withBasicAuth($integration->credential('api_key'), $integration->credential('api_secret'))
            ->get('https://apisms.beem.africa/public/v1/vendors/balance');

        if ($response->successful()) {
            return ['success' => true, 'message' => 'Beem account credentials verified.'];
        }

        return ['success' => false, 'message' => 'Beem rejected these credentials.'];
    }
}
