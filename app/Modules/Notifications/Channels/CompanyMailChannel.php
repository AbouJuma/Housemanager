<?php

namespace App\Modules\Notifications\Channels;

use App\Modules\Email\Services\EmailResolver;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Mail;

/**
 * Routes a notification's email through the recipient's own company's
 * configured mailer (Module 9's EmailResolver) instead of Laravel's
 * global 'mail.default' - a company that's set up its own SMTP/Gmail/
 * Mailgun/etc. integration should have its own notifications sent
 * through it, the same way it would for any other outbound email.
 * Falls back to the system default mailer for a notifiable with no
 * company (SaaS-owner staff) or no active email integration.
 *
 * A notification wanting this channel returns 'company-mail' from
 * via() and implements toCompanyMail($notifiable): array{subject, body}
 * - registered against this class in
 * App\Modules\Notifications\Providers\NotificationsServiceProvider.
 */
class CompanyMailChannel
{
    public function __construct(protected EmailResolver $resolver)
    {
    }

    public function send(mixed $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toCompanyMail')) {
            return;
        }

        $email = $notifiable->routeNotificationFor('mail', $notification) ?? $notifiable->email ?? null;

        if (! $email) {
            return;
        }

        $message = $notification->toCompanyMail($notifiable);
        $company = $notifiable->company ?? null;
        $mailer = $company ? $this->resolver->mailerFor($company) : config('mail.default');

        Mail::mailer($mailer)->raw(
            $message['body'],
            fn ($mail) => $mail->to($email)->subject($message['subject']),
        );
    }
}
