<?php

namespace App\Modules\Users\Notifications;

use App\Notifications\TemplatedNotification;

/**
 * Sent when a company staff account is created - see
 * App\Modules\Users\Services\UserService::create(). Carries how to sign
 * in: staff_name, company_name, phone, username, password, login_url.
 */
class StaffWelcome extends TemplatedNotification
{
    public function __construct(array $data)
    {
        parent::__construct('staff_welcome', $data);
    }

    protected function title(): string
    {
        return 'Welcome message sent';
    }

    protected function summary(mixed $notifiable): string
    {
        return "Login details sent to {$notifiable->name}.";
    }
}
