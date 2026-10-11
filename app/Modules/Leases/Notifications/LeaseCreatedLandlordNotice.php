<?php

namespace App\Modules\Leases\Notifications;

use App\Notifications\TemplatedNotification;

/**
 * Sent to the landlord when a new lease is created against one of their
 * units - see App\Modules\Leases\Services\LeaseService::create(). This
 * is the "new tenant" notice for the landlord: by design it does NOT
 * fire at tenant-creation time, only once a lease actually exists,
 * since that's the point a landlord has something concrete to know
 * about (which unit, which tenant, what rent). Expected $data
 * placeholders (the `lease_landlord_notification` template):
 * landlord_name, property_name, unit_number, tenant_name, tenant_phone,
 * start_date, end_date, rent_amount.
 */
class LeaseCreatedLandlordNotice extends TemplatedNotification
{
    public function __construct(array $data)
    {
        parent::__construct('lease_landlord_notification', $data);
    }

    protected function title(): string
    {
        return 'New tenant notice sent';
    }

    protected function summary(mixed $notifiable): string
    {
        return "New tenant notice sent to {$notifiable->name}.";
    }
}
