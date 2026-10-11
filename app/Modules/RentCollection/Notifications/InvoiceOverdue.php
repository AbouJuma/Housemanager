<?php

namespace App\Modules\RentCollection\Notifications;

use App\Modules\Invoices\Models\Invoice;
use App\Modules\PaymentReminders\Models\PaymentReminderRule;
use App\Notifications\TemplatedNotification;

/**
 * Sent once a tenant's invoice has been marked overdue, per a company's
 * own configured App\Modules\PaymentReminders\Models\
 * PaymentReminderRule (see App\Modules\Invoices\Console\Commands\
 * MarkOverdueInvoices, which runs first in the schedule, and
 * App\Modules\RentCollection\Console\Commands\SendRentReminders, which
 * dispatches this against invoices already in that status). Text comes
 * from the `invoice_overdue` MessageTemplate - see InvoiceDueSoon's
 * docblock for why this extends TemplatedNotification instead of the
 * bare Notification class it used to.
 */
class InvoiceOverdue extends TemplatedNotification
{
    public function __construct(protected Invoice $invoice, protected ?PaymentReminderRule $rule = null)
    {
        $lease = $invoice->lease;
        $unit = $lease->unit;
        $property = $unit->building->property;

        parent::__construct('invoice_overdue', [
            'tenant_name' => $lease->tenant->name,
            'invoice_number' => $invoice->invoice_number,
            'amount' => number_format($invoice->balanceDue(), 0),
            'due_date' => $invoice->due_date->format('M j, Y'),
            'unit_number' => $unit->unit_number,
            'property_name' => $property->name,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(mixed $notifiable): array
    {
        return [
            'invoice_id' => $this->invoice->id,
            'rule_id' => $this->rule?->id,
            'due_date' => $this->invoice->due_date->toDateString(),
            'title' => $this->title(),
            'message' => $this->summary($notifiable),
        ];
    }

    protected function title(): string
    {
        return 'Rent overdue';
    }

    protected function summary(mixed $notifiable): string
    {
        return "Invoice {$this->invoice->invoice_number} for ".number_format($this->invoice->balanceDue(), 2)." was due on {$this->invoice->due_date->format('M j, Y')} and is now overdue.";
    }
}
