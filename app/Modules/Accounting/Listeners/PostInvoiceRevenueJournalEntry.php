<?php

namespace App\Modules\Accounting\Listeners;

use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Services\JournalEntryService;
use App\Modules\Invoices\Events\InvoiceSent;
use App\Modules\Invoices\Models\Invoice;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Recognizes revenue the moment an invoice is sent: debits Accounts
 * Receivable (1200), credits Rental Income (4000), for the invoice's
 * full total. Looks accounts up by the default chart's codes
 * (config('accounting.default_chart'), seeded per company by
 * SeedDefaultChartOfAccountsForCompany) - if a company has renamed,
 * deactivated, or deleted either account, this logs a warning and
 * skips the posting entirely rather than guessing a different account
 * or failing the invoice-sending request itself (ARCHITECTURE.md §12).
 */
class PostInvoiceRevenueJournalEntry
{
    public function __construct(protected JournalEntryService $service)
    {
    }

    public function handle(InvoiceSent $event): void
    {
        $invoice = $event->invoice;

        $receivable = $this->activeAccount($invoice->company_id, '1200');
        $income = $this->activeAccount($invoice->company_id, '4000');

        if (! $receivable || ! $income) {
            Log::warning("Accounting: skipped automatic revenue posting for invoice {$invoice->invoice_number} - default Accounts Receivable/Rental Income account not found or inactive for company {$invoice->company_id}.");

            return;
        }

        try {
            $this->service->post([
                'company_id' => $invoice->company_id,
                'entry_date' => $invoice->issue_date->toDateString(),
                'reference' => $invoice->invoice_number,
                'description' => "Rent invoice {$invoice->invoice_number} issued",
                'source_type' => Invoice::class,
                'source_id' => $invoice->id,
                'lines' => [
                    ['account_id' => $receivable->id, 'debit' => (float) $invoice->total_amount],
                    ['account_id' => $income->id, 'credit' => (float) $invoice->total_amount],
                ],
            ]);
        } catch (Throwable $e) {
            // A bookkeeping-side failure must never surface as a
            // broken "Send Invoice" action to the user - see this
            // class's docblock.
            Log::error("Accounting: failed to post revenue journal entry for invoice {$invoice->invoice_number}: {$e->getMessage()}");
        }
    }

    protected function activeAccount(int $companyId, string $code): ?Account
    {
        return Account::query()->forCompany($companyId)->where('code', $code)->where('is_active', true)->first();
    }
}
