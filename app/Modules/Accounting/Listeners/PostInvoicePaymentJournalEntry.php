<?php

namespace App\Modules\Accounting\Listeners;

use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Services\JournalEntryService;
use App\Modules\Invoices\Events\InvoicePaymentRecorded;
use App\Modules\Invoices\Models\InvoicePayment;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Records cash receipt the moment a payment (manual or gateway) is
 * recorded against an invoice: debits Cash (1000) or Bank (1100)
 * depending on method, credits Accounts Receivable (1200). Same
 * skip-and-log-instead-of-fail discipline as
 * PostInvoiceRevenueJournalEntry - see that class's docblock.
 */
class PostInvoicePaymentJournalEntry
{
    public function __construct(protected JournalEntryService $service)
    {
    }

    public function handle(InvoicePaymentRecorded $event): void
    {
        $payment = $event->payment;
        $invoice = $payment->invoice;

        // bank_transfer/cheque/gateway settle into a bank account, not
        // a physical till - cash/mobile_money/other default to Cash.
        $cashAccountCode = in_array($payment->method, ['bank_transfer', 'cheque', 'gateway'], true) ? '1100' : '1000';

        $cash = $this->activeAccount($payment->company_id, $cashAccountCode);
        $receivable = $this->activeAccount($payment->company_id, '1200');

        if (! $cash || ! $receivable) {
            Log::warning("Accounting: skipped automatic cash-receipt posting for invoice {$invoice->invoice_number} - default Cash/Bank/Accounts Receivable account not found or inactive for company {$payment->company_id}.");

            return;
        }

        try {
            $this->service->post([
                'company_id' => $payment->company_id,
                'entry_date' => $payment->paid_on->toDateString(),
                'reference' => $invoice->invoice_number,
                'description' => "Payment received for invoice {$invoice->invoice_number}",
                'source_type' => InvoicePayment::class,
                'source_id' => $payment->id,
                'lines' => [
                    ['account_id' => $cash->id, 'debit' => (float) $payment->amount],
                    ['account_id' => $receivable->id, 'credit' => (float) $payment->amount],
                ],
            ]);
        } catch (Throwable $e) {
            Log::error("Accounting: failed to post cash-receipt journal entry for invoice {$invoice->invoice_number}: {$e->getMessage()}");
        }
    }

    protected function activeAccount(int $companyId, string $code): ?Account
    {
        return Account::query()->forCompany($companyId)->where('code', $code)->where('is_active', true)->first();
    }
}
