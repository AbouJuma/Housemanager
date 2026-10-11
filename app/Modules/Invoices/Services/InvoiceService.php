<?php

namespace App\Modules\Invoices\Services;

use App\Core\Services\BaseService;
use App\Modules\Invoices\Events\InvoicePaymentRecorded;
use App\Modules\Invoices\Events\InvoiceSent;
use App\Modules\Invoices\Models\Invoice;
use App\Modules\Invoices\Models\InvoicePayment;
use App\Modules\Invoices\Notifications\BuyerInvoicePaymentConfirmation;
use App\Modules\Invoices\Notifications\LandlordPaymentConfirmation;
use App\Modules\Invoices\Notifications\LandlordSalePaymentConfirmation;
use App\Modules\Invoices\Notifications\TenantPaymentConfirmation;
use App\Modules\Invoices\Repositories\InvoiceRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * @property InvoiceRepositoryInterface $repository see App\Modules\Companies\Services\CompanyService
 *  for why this can't be redeclared as a typed constructor property.
 */
class InvoiceService extends BaseService
{
    public function __construct(InvoiceRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    public function paginate(
        ?string $search,
        ?string $status,
        int $perPage = 15,
        ?string $from = null,
        ?string $to = null,
    ): LengthAwarePaginator {
        return $this->repository->query()
            ->with(['lease.tenant', 'lease.unit.building.property', 'sale.buyer', 'sale.unit.building.property'])
            ->when($search, fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('lease.tenant', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('lease.unit', fn ($q) => $q->where('unit_number', 'like', "%{$search}%"))
                    ->orWhereHas('sale.buyer', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('sale.unit', fn ($q) => $q->where('unit_number', 'like', "%{$search}%"));
            }))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($from, fn ($query) => $query->whereDate('due_date', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('due_date', '<=', $to))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * $attributes['items'] is an array of {description, quantity,
     * unit_price} - not persisted columns on `invoices` itself, so it's
     * peeled off before reaching the repository and handed to
     * syncItems() instead.
     */
    public function create(array $attributes): Model
    {
        $items = $attributes['items'] ?? [];
        unset($attributes['items']);

        return DB::transaction(function () use ($attributes, $items) {
            $attributes['invoice_number'] = $this->generateInvoiceNumber($attributes['company_id']);
            $invoice = $this->repository->create($attributes);
            $this->syncItems($invoice, $items);

            return $invoice->fresh('items');
        });
    }

    /**
     * Only draft invoices can be edited - once sent, an invoice is a
     * financial document; correcting it means cancelling and issuing a
     * new one, not silently mutating what the tenant was already shown
     * (the same "no editing past a certain lifecycle point" discipline
     * as Lease's fixed tenant_id/unit_id, ARCHITECTURE.md §10d).
     */
    public function update(Model $model, array $attributes): Model
    {
        if (! $model->isEditable()) {
            throw ValidationException::withMessages(['status' => 'Only draft invoices can be edited.']);
        }

        $items = $attributes['items'] ?? null;
        unset($attributes['items']);

        return DB::transaction(function () use ($model, $attributes, $items) {
            $invoice = $this->repository->update($model, $attributes);

            if ($items !== null) {
                $this->syncItems($invoice, $items);
            }

            return $invoice->fresh('items');
        });
    }

    public function delete(Model $model): bool
    {
        if (! in_array($model->status, ['draft', 'cancelled'], true)) {
            throw ValidationException::withMessages(['status' => 'Only draft or cancelled invoices can be deleted.']);
        }

        return parent::delete($model);
    }

    public function send(Invoice $invoice): Invoice
    {
        if ($invoice->status !== 'draft') {
            throw ValidationException::withMessages(['status' => 'Only draft invoices can be sent.']);
        }

        $invoice->update(['status' => 'sent']);

        // Dispatched after the update commits, not from inside a
        // transaction - a listener's own failure (e.g. Accounting's
        // revenue posting, ARCHITECTURE.md §12) must never roll back
        // the invoice actually being sent.
        InvoiceSent::dispatch($invoice);

        return $invoice;
    }

    public function cancel(Invoice $invoice): Invoice
    {
        if (in_array($invoice->status, ['paid', 'cancelled'], true)) {
            throw ValidationException::withMessages(['status' => 'This invoice cannot be cancelled.']);
        }

        $invoice->update(['status' => 'cancelled']);

        return $invoice;
    }

    /**
     * status is derived from amount_paid vs total_amount, never accepted
     * directly as form/API input - see StoreInvoiceRequest/
     * UpdateInvoiceRequest never exposing it, and RecordInvoicePaymentRequest
     * only accepting payment details. This keeps the two values from
     * ever disagreeing with each other.
     */
    public function recordPayment(Invoice $invoice, array $attributes): InvoicePayment
    {
        if (in_array($invoice->status, ['draft', 'cancelled'], true)) {
            throw ValidationException::withMessages(['status' => 'Payments can only be recorded against sent invoices.']);
        }

        $payment = DB::transaction(function () use ($invoice, $attributes) {
            $payment = $invoice->payments()->create([
                'company_id' => $invoice->company_id,
                ...$attributes,
            ]);

            $totalPaid = round((float) $invoice->payments()->sum('amount'), 2);
            $status = $totalPaid >= (float) $invoice->total_amount ? 'paid' : 'partially_paid';

            $invoice->update(['amount_paid' => $totalPaid, 'status' => $status]);

            return $payment;
        });

        $this->sendPaymentNotifications($invoice, $payment);

        // Same "after commit, outside the transaction" discipline as
        // send()'s InvoiceSent dispatch above.
        InvoicePaymentRecorded::dispatch($payment);

        return $payment;
    }

    /**
     * Notifies tenant+landlord for a lease invoice, or buyer+landlord
     * for a sale invoice.
     */
    protected function sendPaymentNotifications(Invoice $invoice, InvoicePayment $payment): void
    {
        if ($invoice->lease) {
            $this->sendLeasePaymentNotifications($invoice, $payment);
        }

        if ($invoice->sale) {
            $this->sendSalePaymentNotifications($invoice, $payment);
        }
    }

    protected function sendLeasePaymentNotifications(Invoice $invoice, InvoicePayment $payment): void
    {
        $lease = $invoice->lease;
        $tenant = $lease->tenant;
        $unit = $lease->unit;
        $property = $unit->building->property;
        $landlord = $property->landlord;

        $tenant->notify(new TenantPaymentConfirmation([
            'tenant_name' => $tenant->name,
            'invoice_number' => $invoice->invoice_number,
            'amount' => number_format($payment->amount, 0),
            'unit_number' => $unit->unit_number,
            'property_name' => $property->name,
            'payment_date' => $payment->paid_on->format('M j, Y'),
            'balance' => number_format($invoice->balanceDue(), 0),
        ]));

        if ($landlord) {
            $landlord->notify(new LandlordPaymentConfirmation([
                'invoice_number' => $invoice->invoice_number,
                'balance' => number_format($invoice->balanceDue(), 0),
                'amount' => number_format($payment->amount, 0),
                'tenant_name' => $tenant->name,
                'unit_number' => $unit->unit_number,
                'property_name' => $property->name,
                'payment_date' => $payment->paid_on->format('M j, Y'),
            ]));
        }
    }

    protected function sendSalePaymentNotifications(Invoice $invoice, InvoicePayment $payment): void
    {
        $sale = $invoice->sale;
        $buyer = $sale->buyer;
        $unit = $sale->unit;
        $property = $unit->building->property;
        $landlord = $property->landlord;

        // Sale-wide figures (what's left to pay, installments remaining)
        // plus this payment's own details - same data for buyer and
        // landlord, each rendered through their own template.
        $data = [
            ...$sale->messagePlaceholders(),
            'amount' => number_format($payment->amount, 0),
            'payment_date' => $payment->paid_on->format('M j, Y'),
            'installment_number' => $invoice->installment->installment_number,
            'installment_balance' => number_format($invoice->balanceDue(), 0),
            'invoice_number' => $invoice->invoice_number,
        ];

        $buyer->notify(new BuyerInvoicePaymentConfirmation($data));

        $landlord?->notify(new LandlordSalePaymentConfirmation($data));
    }

    /**
     * Sequential per company (INV-000001, INV-000002, ...), generated
     * from a row count rather than a dedicated sequence table or
     * SELECT ... FOR UPDATE lock. Under real concurrent creation for the
     * same company this has a narrow race window; the unique
     * (company_id, invoice_number) index (see the migration) is the
     * actual correctness guarantee - a colliding insert fails loudly
     * instead of silently producing two invoices with the same number.
     * A dedicated counter/lock is a reasonable future upgrade if this
     * app's write concurrency ever justifies it, not before.
     */
    protected function generateInvoiceNumber(int $companyId): string
    {
        $count = Invoice::withTrashed()->where('company_id', $companyId)->count();

        return 'INV-'.str_pad((string) ($count + 1), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Line items are always replaced wholesale on create/update, never
     * diffed - the form always submits the invoice's complete item
     * list, so deleting and recreating is simpler than reconciling
     * adds/removes/edits against existing rows, and total_amount is
     * recalculated here rather than trusted from the request.
     */
    protected function syncItems(Invoice $invoice, array $items): void
    {
        $invoice->items()->delete();

        $total = 0;

        foreach ($items as $item) {
            $quantity = (float) ($item['quantity'] ?? 1);
            $unitPrice = (float) $item['unit_price'];
            $amount = round($quantity * $unitPrice, 2);
            $total += $amount;

            $invoice->items()->create([
                'company_id' => $invoice->company_id,
                'description' => $item['description'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'amount' => $amount,
            ]);
        }

        $invoice->update(['total_amount' => round($total, 2)]);
    }
}
