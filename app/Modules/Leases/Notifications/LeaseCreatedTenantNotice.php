<?php

namespace App\Modules\Leases\Notifications;

use App\Notifications\TemplatedNotification;

/**
 * Sent to the tenant when their lease is created - see
 * App\Modules\Leases\Services\LeaseService::create(). Has its own
 * `lease_created_tenant` template, separate from the tenant_welcome
 * sent when the tenant record is created (App\Modules\Tenants\
 * Notifications\TenantWelcome) - a lease carries data (unit, dates,
 * rent, payment details) a tenant record alone doesn't have.
 * Placeholders: see MessageTemplate::PLACEHOLDERS['lease_created_tenant'].
 */
class LeaseCreatedTenantNotice extends TemplatedNotification
{
    public function __construct(array $data)
    {
        parent::__construct('lease_created_tenant', $data);
    }

    protected function title(): string
    {
        return 'Lease welcome message sent';
    }

    protected function summary(mixed $notifiable): string
    {
        return "Lease welcome message sent to {$notifiable->name}.";
    }
}
