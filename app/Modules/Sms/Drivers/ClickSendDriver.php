<?php

namespace App\Modules\Sms\Drivers;

use App\Modules\Integrations\Contracts\IntegrationTester;
use App\Modules\Integrations\Models\Integration;
use App\Modules\Sms\Contracts\SmsProviderInterface;
use Illuminate\Support\Facades\Http;

/**
 * ClickSend's REST API v3 (https://developers.clicksend.com/docs/rest/v3/)
 * - HTTP Basic Auth using your account username + API key. Credential
 * mapping: api_key = Username, api_secret = API Key, merchant_id =
 * Sender ID (a "from" number/name - optional, ClickSend assigns one
 * automatically when left blank).
 */
class ClickSendDriver implements IntegrationTester, SmsProviderInterface
{
    public function send(Integration $integration, string $to, string $message): array
    {
        $response = Http::withBasicAuth($integration->credential('api_key'), $integration->credential('api_secret'))
            ->post('https://rest.clicksend.com/v3/sms/send', [
                'messages' => [array_filter([
                    'source' => 'php',
                    'body' => $message,
                    'to' => $to,
                    'from' => $integration->credential('merchant_id'),
                ])],
            ]);

        $status = $response->json('data.messages.0.status');

        if ($response->successful() && $status !== 'FAILED') {
            return [
                'success' => true,
                'message' => 'Sent.',
                'message_id' => $response->json('data.messages.0.message_id'),
            ];
        }

        return ['success' => false, 'message' => $response->json('response_msg') ?? $response->body()];
    }

    public function test(Integration $integration): array
    {
        $response = Http::withBasicAuth($integration->credential('api_key'), $integration->credential('api_secret'))
            ->get('https://rest.clicksend.com/v3/account');

        if ($response->successful()) {
            return ['success' => true, 'message' => 'ClickSend account credentials verified.'];
        }

        return ['success' => false, 'message' => $response->json('response_msg') ?? 'ClickSend rejected these credentials.'];
    }
}
