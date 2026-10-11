<?php

namespace App\Modules\Sales\Console\Commands;

use App\Modules\Invoices\Models\Invoice;
use App\Modules\Invoices\Models\InvoicePayment;
use App\Modules\Invoices\Services\InvoiceService;
use App\Modules\Sales\Models\Installment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-time backfill for installments created before SaleService started
 * generating a linked Invoice upfront for every installment - without
 * this, a sale made before that change has a real remaining_balance but
 * zero Invoice rows, which is exactly what made the buyer dashboard's
 * outstanding-balance/unpaid-invoice figures read as "0" despite money
 * genuinely being owed.
 *
 * Deliberately does NOT fire InvoiceSent/notifications - this is a
 * bookkeeping correction for a past sale, not a new bill the buyer
 * needs to be told about, and an already-paid installment's backfilled
 * invoice reflects that payment directly rather than being re-recorded
 * through InvoiceService::recordPayment() (which would otherwise
 * re-notify the buyer/landlord for a payment that already happened and
 * was already communicated at the time).
 */
class BackfillInstallmentInvoices extends Command
{
    protected $signature = 'sales:backfill-installment-invoices';

    protected $description = 'Generate the missing linked invoice for every installment created before invoices were generated upfront';

    public function __construct(protected InvoiceService $invoiceService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $backfilled = 0;

        Installment::query()
            ->whereDoesntHave('invoice')
            ->with('sale.unit.building.property')
            ->chunkById(100, function ($installments) use (&$backfilled): void {
                foreach ($installments as $installment) {
                    if ($this->backfillForInstallment($installment)) {
                        $backfilled++;
                    }
                }
            });

        $this->info("Backfilled {$backfilled} installment invoice(s).");

        return self::SUCCESS;
    }

    protected function backfillForInstallment(Installment $installment): bool
    {
        $sale = $installment->sale;

        if (! $sale) {
            return false;
        }

        $unitLabel = $sale->unit ? "Unit {$sale->unit->unit_number}" : 'Unit';

        DB::transaction(function () use ($installment, $sale, $unitLabel): void {
            $invoice = $this->invoiceService->create([
                'company_id' => $sale->company_id,
                'sale_id' => $sale->id,
                'installment_id' => $installment->id,
                'issue_date' => $installment->created_at?->toDateString() ?? now()->toDateString(),
                'due_date' => $installment->due_date->toDateString(),
                'status' => 'sent',
                'items' => [[
                    'description' => "Installment #{$installment->installment_number} - {$unitLabel}",
                    'quantity' => 1,
                    'unit_price' => (float) $installment->amount,
                ]],
            ]);

            if ((float) $installment->paid_amount > 0) {
                InvoicePayment::create([
                    'company_id' => $sale->company_id,
                    'invoice_id' => $invoice->id,
                    'amount' => $installment->paid_amount,
                    'paid_on' => $installment->paid_date?->toDateString() ?? $installment->due_date->toDateString(),
                    'notes' => 'Backfilled from pre-existing installment payment history.',
                ]);

                $invoice->update([
                    'amount_paid' => $installment->paid_amount,
                    'status' => (float) $installment->paid_amount >= (float) $invoice->total_amount ? 'paid' : 'partially_paid',
                ]);
            }
        });

        return true;
    }
}
