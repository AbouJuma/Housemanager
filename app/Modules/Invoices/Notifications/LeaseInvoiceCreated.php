<?php

namespace App\Modules\Invoices\Notifications;

use App\Notifications\TemplatedNotification;

/**
 * Sent to a tenant whenever one of their lease invoices is created and
 * sent - whether generated automatically by App\Modules\RentCollection\
 * Console\Commands\GenerateRentInvoices or created manually by staff
 * (App\Modules\Invoices\Services\InvoiceService::send()) - both paths
 * dispatch the same App\Modules\Invoices\Events\InvoiceSent event,
 * which App\Modules\Invoices\Listeners\NotifyCustomerOfInvoiceSent
 * listens for and fires this from. Reactivates the `lease_payment`
 * template type, previously seeded but never rendered by anything.
 * Expected $data placeholders: amount, due_date, unit_number,
 * property_name, balance.
 */
class LeaseInvoiceCreated extends TemplatedNotification
{
    public function __construct(array $data)
    {
        parent::__construct('lease_payment', $data);
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
