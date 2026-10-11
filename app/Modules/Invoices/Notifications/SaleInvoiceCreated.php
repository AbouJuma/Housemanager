<?php

namespace App\Modules\Invoices\Notifications;

use App\Notifications\TemplatedNotification;

/**
 * Sent to a buyer whenever one of their sale/installment invoices is
 * created and sent - see App\Modules\Invoices\Notifications\
 * LeaseInvoiceCreated's docblock for the shared InvoiceSent-event
 * wiring (App\Modules\Invoices\Listeners\NotifyCustomerOfInvoiceSent).
 * New `buyer_bill` template type - distinct from `buyer_invoice`
 * (payment CONFIRMATION, an entirely different moment) so a company
 * editing templates isn't stuck with one row meaning two different
 * things. Expected $data placeholders: amount, due_date, unit_number,
 * property_name, balance, installment_number.
 */
class SaleInvoiceCreated extends TemplatedNotification
{
    public function __construct(array $data)
    {
        parent::__construct('buyer_bill', $data);
    }

    protected function title(): string
    {
        return 'New invoice';
    }

    protected function summary(mixed $notifiable): string
    {
        return "New invoice sent to {$notifiable->name}.";
    }
}
