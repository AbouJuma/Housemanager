<?php

namespace App\Modules\Notifications\Channels;

use App\Modules\Sms\Services\SmsManager;
use Illuminate\Notifications\Notification;

/**
 * Routes a notification's SMS through the recipient's own company's
 * configured SMS gateway (Module 10's SmsManager). Silently does
 * nothing if the notifiable has no phone number or no company - this
 * channel is opt-in per notification (via toCompanySms()), so a
 * missing phone just means that one channel doesn't fire, not an
 * error the rest of the notification's delivery should fail over.
 *
 * A notification wanting this channel returns 'company-sms' from
 * via() and implements toCompanySms($notifiable): string - registered
 * against this class in App\Modules\Notifications\Providers\
 * NotificationsServiceProvider.
 */
class CompanySmsChannel
{
    public function __construct(protected SmsManager $manager)
    {
    }

    public function send(mixed $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toCompanySms')) {
            return;
        }

        $phone = $notifiable->phone ?? null;
        $company = $notifiable->company ?? null;

        if (! $phone || ! $company) {
            return;
        }

        $this->manager->send($company, $phone, $notification->toCompanySms($notifiable));
    }
}
