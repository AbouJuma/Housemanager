<?php

namespace App\Modules\Email\Testers;

use App\Modules\Email\Services\EmailResolver;
use App\Modules\Integrations\Contracts\IntegrationTester;
use App\Modules\Integrations\Models\Integration;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Shared by every email provider (all SMTP-reachable - see
 * EmailResolver). Unlike the storage testers, which just write a
 * throwaway file, "testing" an email integration means actually
 * sending one - there's no cheaper way to prove SMTP credentials are
 * correct. Sent to whoever clicked Test Connection, so they can check
 * their own inbox immediately.
 */
class SmtpEmailTester implements IntegrationTester
{
    public function __construct(protected EmailResolver $resolver)
    {
    }

    public function test(Integration $integration): array
    {
        $recipient = Auth::user()?->email ?? $integration->company?->email;

        if (! $recipient) {
            return ['success' => false, 'message' => 'No recipient available to send a test email to.'];
        }

        try {
            $mailer = $this->resolver->mailerForIntegration($integration);

            Mail::mailer($mailer)->raw(
                "This is a test email from your Real Estate ERP's Integrations Center, confirming the {$integration->provider} email provider is configured correctly.",
                fn ($message) => $message->to($recipient)->subject('Integration test email'),
            );

            return ['success' => true, 'message' => "Test email sent to {$recipient}."];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => "Sending failed: {$e->getMessage()}"];
        }
    }
}
