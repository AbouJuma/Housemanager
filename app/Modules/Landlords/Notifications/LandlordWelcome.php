<?php

namespace App\Modules\Landlords\Notifications;

use App\Notifications\TemplatedNotification;

/**
 * Sent when a landlord is created - see
 * App\Modules\Landlords\Services\LandlordService::create(). Expected
 * $data placeholders (the `landlord_welcome` template): landlord_name,
 * company_name, property_names.
 */
class LandlordWelcome extends TemplatedNotification
{
    public function __construct(array $data)
    {
        parent::__construct('landlord_welcome', $data);
    }

    protected function title(): string
    {
        return 'Welcome message sent';
    }

    protected function summary(mixed $notifiable): string
    {
        return "Welcome message sent to {$notifiable->name}.";
    }
}
