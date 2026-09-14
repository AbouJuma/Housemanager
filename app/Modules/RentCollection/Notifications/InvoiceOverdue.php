<?php

namespace App\Modules\RentCollection\Notifications;

use App\Modules\Invoices\Models\Invoice;
use Illuminate\Notifications\Notification;

/**
 * Sent once a tenant's invoice has been marked overdue (see
 * App\Modules\Invoices\Console\Commands\MarkOverdueInvoices, which
 * runs first in the schedule, and
 * App\Modules\RentCollection\Console\Commands\SendRentReminders, which
 * dispatches this against invoices already in that status).
 */
class InvoiceOverdue extends Notification
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
            'title' => 'Rent overdue',
            'message' => "Invoice {$this->invoice->invoice_number} for ".number_format($this->invoice->balanceDue(), 2)." was due on {$this->invoice->due_date->format('M j, Y')} and is now overdue.",
        ];
    }

    /**
     * @return array{subject: string, body: string}
     */
    public function toCompanyMail(mixed $notifiable): array
    {
        $due = $this->invoice->due_date->format('M j, Y');

        return [
            'subject' => "Overdue — Invoice {$this->invoice->invoice_number}",
            'body' => "Hi {$notifiable->name},\n\n"
                ."Invoice {$this->invoice->invoice_number} for "
                .number_format($this->invoice->balanceDue(), 2)." was due on {$due} and remains unpaid.\n\n"
                .'Please arrange payment as soon as possible.',
        ];
    }

    public function toCompanySms(mixed $notifiable): string
    {
        return "Overdue: Invoice {$this->invoice->invoice_number} for ".number_format($this->invoice->balanceDue(), 2)." was due {$this->invoice->due_date->format('M j')}.";
    }
}
