<?php

namespace App\Modules\RentCollection\Notifications;

use App\Modules\Invoices\Models\Invoice;
use Illuminate\Notifications\Notification;

/**
 * Sent to a tenant a few days before an invoice's due date - see
 * App\Modules\RentCollection\Console\Commands\SendRentReminders.
 * Includes SMS, unlike TrialEndingSoon's email-only choice: a rent-due
 * reminder is exactly the "stronger case" that notification's docblock
 * says justifies it (ARCHITECTURE.md §10f). No `url` - there's no
 * Tenant Portal yet (Phase E) for a tenant to click through to.
 */
class InvoiceDueSoon extends Notification
{
    public function __construct(protected Invoice $invoice)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        return ['database', 'company-mail', 'company-sms'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(mixed $notifiable): array
    {
        return [
            'invoice_id' => $this->invoice->id,
            'due_date' => $this->invoice->due_date->toDateString(),
            'title' => 'Rent due soon',
            'message' => "Invoice {$this->invoice->invoice_number} for ".number_format($this->invoice->balanceDue(), 2)." is due on {$this->invoice->due_date->format('M j, Y')}.",
        ];
    }

    /**
     * @return array{subject: string, body: string}
     */
    public function toCompanyMail(mixed $notifiable): array
    {
        $due = $this->invoice->due_date->format('M j, Y');

        return [
            'subject' => "Rent due {$due} — Invoice {$this->invoice->invoice_number}",
            'body' => "Hi {$notifiable->name},\n\n"
                ."A reminder that invoice {$this->invoice->invoice_number} for "
                .number_format($this->invoice->balanceDue(), 2)." is due on {$due}.\n\n"
                .'Please arrange payment before then.',
        ];
    }

    public function toCompanySms(mixed $notifiable): string
    {
        $due = $this->invoice->due_date->format('M j');

        return "Reminder: Invoice {$this->invoice->invoice_number} for ".number_format($this->invoice->balanceDue(), 2)." is due {$due}.";
    }
}
