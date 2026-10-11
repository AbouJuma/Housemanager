<?php

namespace App\Modules\RentCollection\Notifications;

use App\Modules\Invoices\Models\Invoice;
use App\Modules\PaymentReminders\Models\PaymentReminderRule;
use App\Notifications\TemplatedNotification;

/**
 * Sent to a tenant ahead of an invoice's due date, per a company's own
 * configured App\Modules\PaymentReminders\Models\PaymentReminderRule -
 * see App\Modules\RentCollection\Console\Commands\SendRentReminders.
 * Text comes from the `invoice_due_soon` MessageTemplate (via the
 * TemplatedNotification base, same as every other customer-facing
 * message in the app) rather than being hardcoded, so a company can
 * change the wording without a code change.
 *
 * $invoice/$rule stay as typed properties (not folded into $data) since
 * toArray() below needs invoice_id/rule_id for SendRentReminders'
 * per-rule dedupe check - two different due-soon rules (e.g. "30 days
 * before" and "3 days before") both need to fire independently for the
 * same invoice.
 */
class InvoiceDueSoon extends TemplatedNotification
{
    public function __construct(protected Invoice $invoice, protected ?PaymentReminderRule $rule = null)
    {
        $lease = $invoice->lease;
        $unit = $lease->unit;
        $property = $unit->building->property;

        parent::__construct('invoice_due_soon', [
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
        return 'Rent due soon';
    }

    protected function summary(mixed $notifiable): string
    {
        return "Invoice {$this->invoice->invoice_number} for ".number_format($this->invoice->balanceDue(), 2)." is due on {$this->invoice->due_date->format('M j, Y')}.";
    }
}
