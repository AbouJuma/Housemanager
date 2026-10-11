<?php

namespace App\Modules\Invoices\Listeners;

use App\Modules\Invoices\Events\InvoiceSent;
use App\Modules\Invoices\Notifications\LeaseInvoiceCreated;
use App\Modules\Invoices\Notifications\SaleInvoiceCreated;

/**
 * Single listener covering every "an invoice just became visible to the
 * customer" moment app-wide, regardless of what created the invoice -
 * a staff member sending one manually, App\Modules\RentCollection\
 * Console\Commands\GenerateRentInvoices's daily rent-invoice
 * generation, or App\Modules\Sales\Services\SaleService generating a
 * sale's installment invoices upfront. All three dispatch the same
 * InvoiceSent event, so wiring the customer notification here once
 * means none of those call sites need to know about messaging at all.
 */
class NotifyCustomerOfInvoiceSent
{
    public function handle(InvoiceSent $event): void
    {
        $invoice = $event->invoice;

        if ($invoice->lease) {
            $this->notifyLeaseTenant($invoice);

            return;
        }

        // A sale's installment invoices are all generated at once when the
        // sale is created; texting the buyer per installment sent one
        // message per installment. They get a single sale-details message
        // instead (SaleService::create()), then a message per payment and
        // the due/overdue reminders. Only a standalone invoice on a sale
        // (not tied to an installment) still gets a "new bill" text.
        if ($invoice->sale && ! $invoice->installment_id) {
            $this->notifySaleBuyer($invoice);
        }
    }

    protected function notifyLeaseTenant($invoice): void
    {
        $lease = $invoice->lease()->with('tenant', 'unit.building.property')->first();
        $tenant = $lease->tenant;
        $unit = $lease->unit;

        $tenant->notify(new LeaseInvoiceCreated([
            'tenant_name' => $tenant->name,
            'invoice_number' => $invoice->invoice_number,
            'amount' => number_format($invoice->total_amount, 0),
            'due_date' => $invoice->due_date->format('M j, Y'),
            'unit_number' => $unit->unit_number,
            'property_name' => $unit->building->property->name,
            'balance' => number_format($invoice->balanceDue(), 0),
        ]));
    }

    protected function notifySaleBuyer($invoice): void
    {
        $sale = $invoice->sale()->with('buyer', 'unit.building.property')->first();
        $buyer = $sale->buyer;
        $unit = $sale->unit;

        if (! $buyer) {
            return;
        }

        $buyer->notify(new SaleInvoiceCreated([
            'buyer_name' => $buyer->name,
            'amount' => number_format($invoice->total_amount, 0),
            'due_date' => $invoice->due_date->format('M j, Y'),
            'unit_number' => $unit->unit_number,
            'property_name' => $unit->building->property->name,
            'balance' => number_format($invoice->balanceDue(), 0),
            'invoice_number' => $invoice->invoice_number,
        ]));
    }
}
