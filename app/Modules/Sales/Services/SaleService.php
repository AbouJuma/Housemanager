<?php

namespace App\Modules\Sales\Services;

use App\Modules\Invoices\Events\InvoiceSent;
use App\Modules\Invoices\Services\InvoiceService;
use App\Modules\Sales\Models\Installment;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Notifications\SaleCreatedNotice;
use Illuminate\Support\Facades\DB;

/**
 * Buyer welcome messages are NOT sent from here - a Sale always
 * references an existing Buyer (picked via new_buyer_id, see
 * resources/views/create.blade.php), so the buyer already received a
 * BuyerWelcome notification when created -
 * App\Modules\Sales\Http\Controllers\BuyerController::store() is the
 * only place a Buyer is created and therefore the only welcome-message
 * call site.
 */
class SaleService
{
    public function __construct(protected InvoiceService $invoiceService)
    {
    }

    public function create(array $data): Sale
    {
        return DB::transaction(function () use ($data) {
            $sale = Sale::create($data);

            // Generate installment schedule
            $this->generateInstallmentSchedule($sale);

            // Mark as active if down payment is made
            if ($sale->down_payment > 0) {
                $sale->status = 'active';
                $sale->save();
            }

            // One message with the full picture - not one per installment.
            $sale->buyer?->notify(new SaleCreatedNotice($sale->messagePlaceholders()));

            return $sale;
        });
    }

    public function update(Sale $sale, array $data): Sale
    {
        return DB::transaction(function () use ($sale, $data) {
            // Captured before update() mutates the model in place - the
            // old comparison read $sale->installment_period AFTER the
            // update already applied, so it was comparing the new value
            // to itself and this branch could never actually fire.
            $originalPeriod = $sale->installment_period;

            $sale->update($data);

            // If installment period changed, regenerate schedule -
            // delete only invoices with no payments yet (an installment
            // someone already paid against keeps its real invoice/
            // payment history rather than losing it to a schedule
            // change).
            if (isset($data['installment_period']) && $data['installment_period'] != $originalPeriod) {
                $sale->installments()->get()->each(function (Installment $installment): void {
                    $installment->invoice()->whereDoesntHave('payments')->delete();
                });
                $sale->installments()->delete();
                $this->generateInstallmentSchedule($sale);
            }

            return $sale;
        });
    }

    public function delete(Sale $sale): void
    {
        DB::transaction(function () use ($sale) {
            $sale->installments()->delete();
            $sale->delete();
        });
    }

    /**
     * Finds the installment's linked invoice (generated upfront by
     * generateInstallmentSchedule() below, every installment has one
     * from the moment the sale is created) and records the payment
     * against it via the shared InvoiceService::recordPayment() - the
     * exact same path a tenant's rent payment goes through. That method
     * already fires the correct buyer+landlord notifications for a
     * sale-linked invoice (sendSalePaymentNotifications()), so nothing
     * sale-specific needs to happen here beyond updating the
     * installment's own denormalized paid_amount/status.
     */
    public function recordPayment(Installment $installment, float $amount): void
    {
        DB::transaction(function () use ($installment, $amount) {
            $installment->paid_amount += $amount;

            if ($installment->paid_amount >= $installment->amount) {
                $installment->status = 'paid';
                $installment->paid_date = now();
            } else {
                $installment->status = 'partially_paid';
            }

            $installment->save();

            $invoice = $installment->invoice;

            if ($invoice) {
                $this->invoiceService->recordPayment($invoice, ['amount' => $amount, 'paid_on' => now()->toDateString()]);
            }

            $sale = $installment->sale;

            if ($sale->remaining_balance <= 0) {
                $sale->markAsCompleted();
            }
        });
    }

    /**
     * Every installment gets its own Invoice the moment the schedule is
     * generated (not reactively after payment, as before) - reuses
     * InvoiceService::create() so numbering/item-total calculation
     * stays identical to a staff-created invoice, and fires the same
     * InvoiceSent event a manually-sent invoice would (revenue posting
     * listens to it). NotifyCustomerOfInvoiceSent deliberately does NOT
     * text the buyer for these - see its docblock.
     */
    protected function generateInstallmentSchedule(Sale $sale): void
    {
        $remainingAmount = $sale->total_price - $sale->down_payment;
        $installmentAmount = $sale->installment_amount;
        $period = $sale->installment_period;

        // Calculate monthly installment amount if not provided
        if ($installmentAmount == 0 && $period > 0) {
            $installmentAmount = $remainingAmount / $period;
        }

        $startDate = $sale->sale_date->copy()->addMonth();
        $unitLabel = $sale->unit ? "Unit {$sale->unit->unit_number}" : 'Unit';

        for ($i = 1; $i <= $period; $i++) {
            $dueDate = $startDate->copy()->addMonths($i - 1);

            $installment = Installment::create([
                'sale_id' => $sale->id,
                'uuid' => (string) \Illuminate\Support\Str::uuid(),
                'installment_number' => $i,
                'due_date' => $dueDate,
                'amount' => $installmentAmount,
                'status' => 'pending',
            ]);

            $invoice = $this->invoiceService->create([
                'company_id' => $sale->company_id,
                'sale_id' => $sale->id,
                'installment_id' => $installment->id,
                'issue_date' => now()->toDateString(),
                'due_date' => $dueDate->toDateString(),
                'status' => 'sent',
                'items' => [[
                    'description' => "Installment #{$i} - {$unitLabel}",
                    'quantity' => 1,
                    'unit_price' => (float) $installmentAmount,
                ]],
            ]);

            InvoiceSent::dispatch($invoice);
        }
    }

    public function paginate(?string $search = null, ?string $status = null, ?int $propertyId = null)
    {
        $query = Sale::with(['unit.building.property', 'buyer']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('unit', function ($unitQuery) use ($search) {
                    $unitQuery->where('unit_number', 'like', "%{$search}%");
                })
                ->orWhereHas('buyer', function ($buyerQuery) use ($search) {
                    $buyerQuery->where('name', 'like', "%{$search}%");
                });
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($propertyId) {
            $query->whereHas('unit.building', function ($q) use ($propertyId) {
                $q->where('property_id', $propertyId);
            });
        }

        return $query->latest()->paginate(20);
    }
}
