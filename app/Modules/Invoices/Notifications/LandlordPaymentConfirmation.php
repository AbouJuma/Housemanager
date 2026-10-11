<?php

namespace App\Modules\Invoices\Notifications;

use App\Notifications\TemplatedNotification;

/**
 * Sent to a landlord once a payment is recorded against one of their
 * units - either a tenant's rent invoice or a buyer's installment
 * invoice, see App\Modules\Invoices\Services\InvoiceService::
 * recordPayment(). Both call sites share this one class/template type
 * (`landlord_payment_notification`) - only the $data (tenant vs buyer
 * name) differs. Expected $data placeholders: amount, tenant_name,
 * unit_number, property_name, payment_date.
 */
class LandlordPaymentConfirmation extends TemplatedNotification
{
    public function __construct(array $data)
    {
        parent::__construct('landlord_payment_notification', $data);
    }

    protected function title(): string
    {
        return 'Payment notification sent';
    }

    protected function summary(mixed $notifiable): string
    {
        return "Payment notification sent to {$notifiable->name}.";
    }
}
