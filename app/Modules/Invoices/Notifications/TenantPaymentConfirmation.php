<?php

namespace App\Modules\Invoices\Notifications;

use App\Notifications\TemplatedNotification;

/**
 * Sent to a tenant once their rent payment is recorded - see
 * App\Modules\Invoices\Services\InvoiceService::recordPayment(). Now
 * sends email alongside SMS (the `tenant_payment_notification`
 * template already had email_subject/email_template populated - it was
 * simply never rendered before this class existed). Expected $data
 * placeholders: amount, unit_number, property_name, payment_date,
 * balance.
 */
class TenantPaymentConfirmation extends TemplatedNotification
{
    public function __construct(array $data)
    {
        parent::__construct('tenant_payment_notification', $data);
    }

    protected function title(): string
    {
        return 'Payment confirmation sent';
    }

    protected function summary(mixed $notifiable): string
    {
        return "Payment confirmation sent to {$notifiable->name}.";
    }
}
