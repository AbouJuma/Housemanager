<?php

namespace App\Modules\Notifications\Channels;

use App\Modules\Notifications\Models\MessageLog;
use App\Modules\Sms\Services\SmsManager;
use Illuminate\Notifications\Notification;
use Throwable;

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
 *
 * Every attempted send is recorded to MessageLog (success or failure) -
 * this is the single choke point every company-sms notification passes
 * through, so logging here covers all of them without touching each
 * notification class individually.
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

        $message = $notification->toCompanySms($notifiable);

        // No active template configured for this type - nothing to
        // send, not a failure worth logging (see TemplatedNotification).
        if ($message === '') {
            return;
        }

        try {
            $result = $this->manager->send($company, $phone, $message);
        } catch (Throwable $e) {
            $result = ['success' => false, 'message' => $e->getMessage()];
        }

        MessageLog::create([
            'company_id' => $company->id,
            'notifiable_type' => $notifiable->getMorphClass(),
            'notifiable_id' => $notifiable->getKey(),
            'channel' => 'sms',
            'recipient' => $phone,
            'message_type' => class_basename($notification),
            'status' => ($result['success'] ?? false) ? 'sent' : 'failed',
            'provider' => $result['provider'] ?? null,
            'error_message' => ($result['success'] ?? false) ? null : ($result['message'] ?? 'Unknown SMS failure.'),
            'sent_at' => now(),
        ]);
    }
}
