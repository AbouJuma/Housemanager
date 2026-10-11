<?php

namespace App\Modules\Tenants\Notifications;

use App\Notifications\TemplatedNotification;

/**
 * Sent when a tenant is created - see
 * App\Modules\Tenants\Services\TenantService::create(). A new tenant has
 * no lease yet, so only what exists at that point is offered:
 * tenant_name, company_name, phone, control_number. Lease details go
 * out in LeaseCreatedTenantNotice instead.
 */
class TenantWelcome extends TemplatedNotification
{
    public function __construct(array $data)
    {
        parent::__construct('tenant_welcome', $data);
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
