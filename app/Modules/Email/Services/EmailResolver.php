<?php

namespace App\Modules\Email\Services;

use App\Modules\Companies\Models\Company;
use App\Modules\Integrations\Models\Integration;
use Illuminate\Support\Facades\Config;
use RuntimeException;

/**
 * Resolves which Laravel mailer a company's outbound email should use,
 * based on its active "email" integration (Module 7 - Integrations
 * Center). Every catalog provider (smtp/gmail/microsoft365/mailgun/
 * sendgrid/ses) is reachable over plain SMTP - Gmail/365 app passwords,
 * Mailgun/SendGrid/SES SMTP credentials - so one transport covers all
 * six without extra composer packages. No active integration falls
 * back to config('mail.default') (whatever ops set in .env).
 *
 * Same fixed-mailer-name pattern as StorageResolver (see
 * App\Modules\Storage\Services\StorageResolver) and for the same
 * reason: only one company is ever in scope per request, and this app
 * targets non-persistent PHP workers where Config::set() never
 * survives past the request that made it.
 */
class EmailResolver
{
    public const COMPANY_MAILER = 'company';

    /**
     * @var array<string, array{host: string, port: int, encryption: string}>
     */
    protected const PRESETS = [
        'gmail' => ['host' => 'smtp.gmail.com', 'port' => 587, 'encryption' => 'tls'],
        'microsoft365' => ['host' => 'smtp.office365.com', 'port' => 587, 'encryption' => 'tls'],
        'mailgun' => ['host' => 'smtp.mailgun.org', 'port' => 587, 'encryption' => 'tls'],
        'sendgrid' => ['host' => 'smtp.sendgrid.net', 'port' => 587, 'encryption' => 'tls'],
        'ses' => ['host' => 'email-smtp.us-east-1.amazonaws.com', 'port' => 587, 'encryption' => 'tls'],
    ];

    public function mailerFor(Company $company): string
    {
        $integration = Integration::query()
            ->forCompany($company->id)
            ->where('type', 'email')
            ->where('is_active', true)
            ->first();

        if (! $integration) {
            return config('mail.default');
        }

        return $this->mailerForIntegration($integration);
    }

    /**
     * Builds a mailer from this specific integration's credentials
     * regardless of its is_active flag - what "Test Connection" needs,
     * so a provider can be verified before being switched on.
     */
    public function mailerForIntegration(Integration $integration): string
    {
        $preset = self::PRESETS[$integration->provider] ?? [];

        $host = $integration->credential('api_key') ?: ($preset['host'] ?? null);

        if (! $host) {
            throw new RuntimeException("No SMTP host configured for [{$integration->provider}] — set the Host field.");
        }

        $encryption = $integration->credential('merchant_id') ?: ($preset['encryption'] ?? 'tls');

        Config::set('mail.mailers.'.self::COMPANY_MAILER, [
            'transport' => 'smtp',
            'host' => $host,
            'port' => (int) ($integration->credential('api_secret') ?: ($preset['port'] ?? 587)),
            'encryption' => $encryption === 'none' ? null : $encryption,
            'username' => $integration->credential('username'),
            'password' => $integration->credential('password'),
        ]);

        return self::COMPANY_MAILER;
    }
}
