<?php

namespace App\Modules\Sms\Drivers;

use App\Modules\Integrations\Contracts\IntegrationTester;
use App\Modules\Integrations\Models\Integration;
use App\Modules\Sms\Contracts\SmsProviderInterface;
use Illuminate\Support\Facades\Http;

/**
 * Twilio's REST API (https://www.twilio.com/docs/sms/api) - Account
 * SID + Auth Token as HTTP Basic Auth, a stable, long-documented
 * endpoint shape. Credential mapping: api_key = Account SID, api_secret
 * = Auth Token, merchant_id = From (a Twilio number or Messaging
 * Service SID).
 */
class TwilioDriver implements IntegrationTester, SmsProviderInterface
{
    public function send(Integration $integration, string $to, string $message): array
    {
        $sid = $integration->credential('api_key');
        $token = $integration->credential('api_secret');

        $response = Http::asForm()
            ->withBasicAuth($sid, $token)
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                'To' => $to,
                'From' => $integration->credential('merchant_id'),
                'Body' => $message,
            ]);

        if ($response->successful()) {
            return ['success' => true, 'message' => 'Sent.', 'message_id' => $response->json('sid')];
        }

        return ['success' => false, 'message' => $response->json('message') ?? $response->body()];
    }

    public function test(Integration $integration): array
    {
        $sid = $integration->credential('api_key');

        $response = Http::withBasicAuth($sid, $integration->credential('api_secret'))
            ->get("https://api.twilio.com/2010-04-01/Accounts/{$sid}.json");

        if ($response->successful()) {
            return ['success' => true, 'message' => 'Twilio account credentials verified.'];
        }

        return ['success' => false, 'message' => $response->json('message') ?? 'Twilio rejected these credentials.'];
    }
}
