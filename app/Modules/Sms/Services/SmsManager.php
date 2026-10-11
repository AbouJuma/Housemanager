<?php

namespace App\Modules\Sms\Services;

use App\Modules\Companies\Models\Company;
use App\Modules\Integrations\Models\Integration;
use App\Modules\Sms\Contracts\SmsProviderInterface;
use App\Modules\Sms\Drivers\BeemDriver;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Company-scoped entry point for sending an SMS: resolves whichever
 * gateway a company has switched on (Module 7's Integrations Center)
 * and delegates to its driver (config('integrations.sms_drivers')).
 * Failures are returned, not thrown - "no active provider" and "the
 * gateway rejected the message" are both just results a caller (e.g.
 * App\Modules\Notifications\Channels\CompanySmsChannel) needs to branch
 * on, not exceptional states.
 *
 * A company with no active SMS integration configured falls back to a
 * platform-wide Beem Africa account (config('services.beems'), backed
 * by required BEEMS_API_KEY/BEEMS_SECRET_KEY/BEEMS_SENDER_NAME env
 * vars - no committed default, unlike the pre-consolidation hardcoded
 * key this replaces) so a company that hasn't set up its own SMS
 * provider yet still gets automated messages out, rather than going
 * silent. Formats destination numbers for Tanzania (0... -> 255...)
 * only on this fallback path - a company's own configured Beem
 * integration receives numbers exactly as entered, unchanged from
 * today's behavior.
 */
class SmsManager
{
    public function send(Company $company, string $to, string $message): array
    {
        $integration = Integration::query()
            ->forCompany($company->id)
            ->where('type', 'sms')
            ->where('is_active', true)
            ->first();

        if (! $integration) {
            return $this->sendViaPlatformFallback($to, $message);
        }

        $driver = $this->driverFor($integration);

        if (! $driver) {
            return ['success' => false, 'message' => "No SMS driver registered for [{$integration->provider}]."];
        }

        return [...$driver->send($integration, $to, $message), 'provider' => $integration->provider];
    }

    /**
     * Exposes whether sendViaPlatformFallback() would actually have
     * something to fall back to, without exposing the credential
     * values themselves - used by the Integrations edit screen to tell
     * an admin whether "no SMS integration configured" really means
     * "no messages will send" or "messages are quietly using the
     * platform's shared account."
     */
    public function isPlatformFallbackAvailable(): bool
    {
        return (bool) (config('services.beems.api_key') && config('services.beems.secret_key'));
    }

    /**
     * The platform's own shared Beem Africa credit balance (Beem's
     * "vendor balance" endpoint - the same one BeemDriver::test() already
     * hits to verify a company's own credentials, just parsed here for
     * its actual value) - shown on the Super Admin dashboard so the SMS
     * fallback every company without its own provider relies on
     * (sendViaPlatformFallback() above) doesn't silently run dry.
     *
     * @return array{success: bool, balance: ?float, message: ?string}
     */
    public function getPlatformSmsBalance(): array
    {
        if (! $this->isPlatformFallbackAvailable()) {
            return ['success' => false, 'balance' => null, 'message' => 'Platform SMS fallback is not configured.'];
        }

        try {
            $response = Http::withBasicAuth(config('services.beems.api_key'), config('services.beems.secret_key'))
                ->timeout(5)
                ->get('https://apisms.beem.africa/public/v1/vendors/balance');
        } catch (\Throwable $e) {
            Log::warning('Beem balance check failed to connect.', ['error' => $e->getMessage()]);

            return ['success' => false, 'balance' => null, 'message' => 'Could not reach Beem.'];
        }

        if (! $response->successful()) {
            return ['success' => false, 'balance' => null, 'message' => 'Beem rejected the platform credentials.'];
        }

        // Beem's documented shape is {"data": {"credit_balance": "..."}}
        // - the top-level fallbacks guard against an undocumented
        // variation rather than assuming the exact shape sight-unseen
        // (see BeemDriver's own confidence note: this integration hasn't
        // been exercised against a live account).
        $balance = $response->json('data.credit_balance')
            ?? $response->json('credit_balance')
            ?? $response->json('balance');

        if ($balance === null) {
            Log::warning('Beem balance response did not contain a recognizable balance field.', ['body' => $response->json()]);

            return ['success' => false, 'balance' => null, 'message' => 'Unrecognized response from Beem.'];
        }

        return ['success' => true, 'balance' => (float) $balance, 'message' => null];
    }

    public function driverFor(Integration $integration): ?SmsProviderInterface
    {
        $class = config("integrations.sms_drivers.{$integration->provider}");

        if (! $class || ! is_subclass_of($class, SmsProviderInterface::class)) {
            return null;
        }

        return app($class);
    }

    protected function sendViaPlatformFallback(string $to, string $message): array
    {
        $apiKey = config('services.beems.api_key');
        $apiSecret = config('services.beems.secret_key');

        if (! $apiKey || ! $apiSecret) {
            return ['success' => false, 'message' => 'No active SMS provider configured for this company.'];
        }

        $integration = new Integration([
            'provider' => 'beem',
            'credentials' => [
                'api_key' => $apiKey,
                'api_secret' => $apiSecret,
                'merchant_id' => config('services.beems.sender_name'),
            ],
        ]);

        $result = app(BeemDriver::class)->send($integration, $this->formatTanzanianPhone($to), $message);

        return [...$result, 'provider' => 'beem-platform-fallback'];
    }

    /**
     * Handles 0682676819 / +255682676819 / 255682676819 shaped numbers -
     * moved from the old App\Modules\Tenants\Services\BeemsSmsService,
     * which this fallback path replaces.
     */
    protected function formatTanzanianPhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($phone, '0')) {
            $phone = '255'.substr($phone, 1);
        }

        if (! str_starts_with($phone, '255')) {
            $phone = '255'.$phone;
        }

        return $phone;
    }
}
