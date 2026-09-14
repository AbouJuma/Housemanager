<?php

namespace App\Modules\Sms\Drivers;

use App\Modules\Integrations\Contracts\IntegrationTester;
use App\Modules\Integrations\Models\Integration;
use App\Modules\Sms\Contracts\SmsProviderInterface;
use Illuminate\Support\Facades\Http;

/**
 * Infobip's REST API (https://www.infobip.com/docs/api) - an
 * "Authorization: App {api_key}" header, and an account-specific base
 * URL (every Infobip account is issued its own subdomain - there's no
 * shared default the way Twilio has one host for everyone). Credential
 * mapping: api_key = API Key, merchant_id = Sender (From), username =
 * Base URL (host only, no scheme - e.g. "xxxxx.api.infobip.com").
 */
class InfobipDriver implements IntegrationTester, SmsProviderInterface
{
    public function send(Integration $integration, string $to, string $message): array
    {
        $response = Http::withHeaders(['Authorization' => 'App '.$integration->credential('api_key')])
            ->post('https://'.$this->baseUrl($integration).'/sms/2/text/advanced', [
                'messages' => [[
                    'destinations' => [['to' => $to]],
                    'from' => $integration->credential('merchant_id'),
                    'text' => $message,
                ]],
            ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'message' => 'Sent.',
                'message_id' => $response->json('messages.0.messageId'),
            ];
        }

        return ['success' => false, 'message' => $response->json('requestError.serviceException.text') ?? $response->body()];
    }

    public function test(Integration $integration): array
    {
        $response = Http::withHeaders(['Authorization' => 'App '.$integration->credential('api_key')])
            ->get('https://'.$this->baseUrl($integration).'/account/1/balance');

        if ($response->successful()) {
            return ['success' => true, 'message' => 'Infobip account credentials verified.'];
        }

        return ['success' => false, 'message' => $response->json('requestError.serviceException.text') ?? 'Infobip rejected these credentials.'];
    }

    protected function baseUrl(Integration $integration): string
    {
        return $integration->credential('username') ?: 'api.infobip.com';
    }
}
