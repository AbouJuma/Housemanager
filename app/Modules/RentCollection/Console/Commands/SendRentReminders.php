<?php

namespace App\Modules\RentCollection\Console\Commands;

use App\Modules\Invoices\Models\Invoice;
use App\Modules\RentCollection\Notifications\InvoiceDueSoon;
use App\Modules\RentCollection\Notifications\InvoiceOverdue;
use App\Modules\Tenants\Models\Tenant;
use Illuminate\Console\Command;

/**
 * Scheduled daily (see RentCollectionServiceProvider::boot()). Runs
 * after MarkOverdueInvoices in the schedule so a freshly-overdue
 * invoice gets its InvoiceOverdue reminder the same day it flips,
 * rather than a day late.
 */
class SendRentReminders extends Command
{
    protected $signature = 'rent:send-reminders';

    protected $description = 'Send due-soon and overdue rent reminders to tenants';

    public function handle(): int
    {
        $dueSoon = $this->sendDueSoonReminders();
        $overdue = $this->sendOverdueReminders();

        $this->info("Sent {$dueSoon} due-soon and {$overdue} overdue reminder(s).");

        return self::SUCCESS;
    }

    /**
     * Fires exactly once per invoice, 3 days before its due date - the
     * same "exact day, not a rolling window" discipline
     * NotifyTrialsEndingSoon uses, so re-running the command daily
     * doesn't re-notify. due_date is immutable once an invoice is sent
     * (Module 17's editable-only-while-draft rule), so a bare
     * invoice_id dedupe check is sufficient - no due_date sub-match
     * needed the way TrialEndingSoon needs a trial_ends_at sub-match.
     */
    protected function sendDueSoonReminders(): int
    {
        $targetDate = now()->addDays(3)->toDateString();

        $invoices = Invoice::query()
            ->whereIn('status', ['sent', 'partially_paid'])
            ->whereDate('due_date', $targetDate)
            ->with('lease.tenant')
            ->get();

        $sent = 0;

        foreach ($invoices as $invoice) {
            $tenant = $invoice->lease->tenant;

            if ($this->alreadyNotified($tenant, InvoiceDueSoon::class, $invoice->id)) {
                continue;
            }

            $tenant->notify(new InvoiceDueSoon($invoice));
            $sent++;
        }

        return $sent;
    }

    /**
     * Re-notifies every 7 days while an invoice stays overdue, unlike
     * the due-soon reminder's one-shot - a debt that's still unpaid a
     * week later deserves another nudge, not permanent silence.
     */
    protected function sendOverdueReminders(): int
    {
        $invoices = Invoice::query()->where('status', 'overdue')->with('lease.tenant')->get();

        $sent = 0;

        foreach ($invoices as $invoice) {
            $tenant = $invoice->lease->tenant;

            if ($this->alreadyNotified($tenant, InvoiceOverdue::class, $invoice->id, withinDays: 7)) {
                continue;
            }

            $tenant->notify(new InvoiceOverdue($invoice));
            $sent++;
        }

        return $sent;
    }

    protected function alreadyNotified(Tenant $tenant, string $notificationClass, int $invoiceId, ?int $withinDays = null): bool
    {
        return $tenant->notifications()
            ->where('type', $notificationClass)
            ->where('data->invoice_id', $invoiceId)
            ->when($withinDays, fn ($query) => $query->where('created_at', '>=', now()->subDays($withinDays)))
            ->exists();
    }
}
