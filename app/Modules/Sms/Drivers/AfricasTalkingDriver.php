<?php

namespace App\Modules\Sms\Drivers;

use App\Modules\Integrations\Contracts\IntegrationTester;
use App\Modules\Integrations\Models\Integration;
use App\Modules\Sms\Contracts\SmsProviderInterface;
use Illuminate\Support\Facades\Http;

/**
 * Africa's Talking REST API
 * (https://developers.africastalking.com/docs/sms/overview) - a custom
 * "apiKey" header plus a required "username" (your AT application
 * username, or "sandbox" for the sandbox app). Credential mapping:
 * api_key = Username, api_secret = API Key, merchant_id = Sender ID /
 * Short Code (optional).
 */
class AfricasTalkingDriver implements IntegrationTester, SmsProviderInterface
{
    public function send(Integration $integration, string $to, string $message): array
    {
        $response = Http::asForm()
            ->withHeaders(['apiKey' => $integration->credential('api_secret'), 'Accept' => 'application/json'])
            ->post('https://api.africastalking.com/version1/messaging', array_filter([
                'username' => $integration->credential('api_key'),
                'to' => $to,
                'message' => $message,
                'from' => $integration->credential('merchant_id'),
            ]));

        $status = $response->json('SMSMessageData.Recipients.0.status');

        if ($response->successful() && $status === 'Success') {
            return [
                'success' => true,
                'message' => 'Sent.',
                'message_id' => $response->json('SMSMessageData.Recipients.0.messageId'),
            ];
        }

        return ['success' => false, 'message' => $status ?? $response->body()];
    }

    public function test(Integration $integration): array
    {
        $response = Http::withHeaders(['apiKey' => $integration->credential('api_secret'), 'Accept' => 'application/json'])
            ->get('https://api.africastalking.com/version1/user', [
                'username' => $integration->credential('api_key'),
            ]);

        if ($response->successful()) {
            return ['success' => true, 'message' => "Africa's Talking account credentials verified."];
        }

        return ['success' => false, 'message' => "Africa's Talking rejected these credentials."];
    }
}
