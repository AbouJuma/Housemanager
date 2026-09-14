<?php

namespace App\Modules\Invoices\Console\Commands;

use App\Modules\Invoices\Models\Invoice;
use Illuminate\Console\Command;

/**
 * Scheduled daily (see InvoicesServiceProvider::boot() - the same
 * precedent as Leases' ExpireLeases, ARCHITECTURE.md §10d). An invoice
 * that's sent or partially paid and whose due_date has passed without
 * being fully paid becomes overdue on its own; paying it afterwards
 * still moves it to paid via the normal recordPayment() path (overdue
 * isn't a dead end).
 */
class MarkOverdueInvoices extends Command
{
    protected $signature = 'invoices:mark-overdue';

    protected $description = 'Mark sent/partially paid invoices whose due date has passed as overdue';

    public function handle(): int
    {
        $count = Invoice::query()
            ->whereIn('status', ['sent', 'partially_paid'])
            ->whereDate('due_date', '<', now()->toDateString())
            ->update(['status' => 'overdue']);

        $this->info("Marked {$count} invoice(s) as overdue.");

        return self::SUCCESS;
    }
}
