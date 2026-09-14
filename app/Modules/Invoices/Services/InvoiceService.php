<?php

namespace App\Modules\Invoices\Services;

use App\Core\Services\BaseService;
use App\Modules\Invoices\Events\InvoicePaymentRecorded;
use App\Modules\Invoices\Events\InvoiceSent;
use App\Modules\Invoices\Models\Invoice;
use App\Modules\Invoices\Models\InvoicePayment;
use App\Modules\Invoices\Repositories\InvoiceRepositoryInterface;
use App\Modules\Tenants\Services\BeemsSmsService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/**
 * @property InvoiceRepositoryInterface $repository see App\Modules\Companies\Services\CompanyService
 *  for why this can't be redeclared as a typed constructor property.
 */
class InvoiceService extends BaseService
{
    protected BeemsSmsService $smsService;

    public function __construct(InvoiceRepositoryInterface $repository, BeemsSmsService $smsService)
    {
        parent::__construct($repository);
        $this->smsService = $smsService;
    }

    public function paginate(?string $search, ?string $status, int $perPage = 15): LengthAwarePaginator
    {
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

        // Send payment notifications
        $this->sendPaymentNotifications($invoice, $payment);

        // Same "after commit, outside the transaction" discipline as
        // send()'s InvoiceSent dispatch above.
        InvoicePaymentRecorded::dispatch($payment);

        return $payment;
    }

    /**
     * Send payment notifications based on invoice type
     */
    protected function sendPaymentNotifications(Invoice $invoice, InvoicePayment $payment): void
    {
        // If it's a lease invoice, send to tenant and landlord
        if ($invoice->lease) {
            $this->sendLeasePaymentNotifications($invoice, $payment);
        }
        
        // If it's a sale invoice, send to buyer and landlord
        if ($invoice->sale) {
            $this->sendBuyerPaymentNotifications($invoice, $payment);
        }
    }

    /**
     * Send payment notifications for lease invoices
     */
    protected function sendLeasePaymentNotifications(Invoice $invoice, InvoicePayment $payment): void
    {
        $lease = $invoice->lease;
        $tenant = $lease->tenant;
        $unit = $lease->unit;
        $property = $unit->building->property;
        $landlord = $property->landlord;

        // Send notification to tenant
        $this->sendTenantPaymentNotification($invoice, $tenant, $unit, $property, $payment);

        // Send notification to landlord
        if ($landlord) {
            $this->sendLandlordLeasePaymentNotification($invoice, $tenant, $unit, $property, $landlord, $payment);
        }
    }

    /**
     * Send payment notification to tenant
     */
    protected function sendTenantPaymentNotification(Invoice $invoice, $tenant, $unit, $property, InvoicePayment $payment): void
    {
        try {
            $data = [
                'amount' => number_format($payment->amount, 0),
                'unit_number' => $unit->unit_number,
                'property_name' => $property->name,
                'payment_date' => $payment->paid_on,
                'balance' => number_format($invoice->balanceDue(), 0),
            ];

            // Get message template
            $template = \App\Models\MessageTemplate::whereNull('company_id')
                ->where('type', 'tenant_payment_notification')
                ->where('is_active', true)
                ->first();

            if (!$template || !$tenant->phone) {
                Log::warning('Cannot send tenant payment notification', [
                    'has_template' => $template !== null,
                    'has_phone' => $tenant->phone !== null
                ]);
                return;
            }

            $smsMessage = $template->renderSmsTemplate($data);
            Log::info('Sending payment notification to tenant', ['phone' => $tenant->phone, 'message' => $smsMessage]);
            $this->smsService->send($tenant->phone, $smsMessage);
        } catch (\Exception $e) {
            Log::error('Error sending tenant payment notification', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send payment notification to landlord for lease payment
     */
    protected function sendLandlordLeasePaymentNotification(Invoice $invoice, $tenant, $unit, $property, $landlord, InvoicePayment $payment): void
    {
        try {
            $data = [
                'amount' => number_format($payment->amount, 0),
                'tenant_name' => $tenant->name,
                'unit_number' => $unit->unit_number,
                'property_name' => $property->name,
                'payment_date' => $payment->paid_on,
            ];

            // Get message template
            $template = \App\Models\MessageTemplate::whereNull('company_id')
                ->where('type', 'landlord_payment_notification')
                ->where('is_active', true)
                ->first();

            if (!$template || !$landlord->phone) {
                Log::warning('Cannot send landlord lease payment notification', [
                    'has_template' => $template !== null,
                    'has_phone' => $landlord->phone !== null
                ]);
                return;
            }

            $smsMessage = $template->renderSmsTemplate($data);
            Log::info('Sending lease payment notification to landlord', ['phone' => $landlord->phone, 'message' => $smsMessage]);
            $this->smsService->send($landlord->phone, $smsMessage);
        } catch (\Exception $e) {
            Log::error('Error sending landlord lease payment notification', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send payment notifications for buyer invoices
     */
    protected function sendBuyerPaymentNotifications(Invoice $invoice, InvoicePayment $payment): void
    {
        $sale = $invoice->sale;
        $buyer = $sale->buyer;
        $unit = $sale->unit;
        $property = $unit->building->property;
        $landlord = $property->landlord;

        // Send notification to buyer
        $this->sendBuyerInvoicePaymentNotification($invoice, $buyer, $unit, $property, $payment);

        // Send notification to landlord
        if ($landlord) {
            $this->sendLandlordBuyerPaymentNotification($invoice, $buyer, $unit, $property, $landlord, $payment);
        }
    }

    /**
     * Send payment notification to buyer for invoice payment
     */
    protected function sendBuyerInvoicePaymentNotification(Invoice $invoice, $buyer, $unit, $property, InvoicePayment $payment): void
    {
        try {
            $data = [
                'amount' => number_format($payment->amount, 0),
                'unit_number' => $unit->unit_number,
                'property_name' => $property->name,
                'payment_date' => $payment->paid_on,
                'balance' => number_format($invoice->balanceDue(), 0),
                'payment_terms' => $invoice->payment_terms ?? 'N/A',
                'invoice_number' => $invoice->invoice_number,
            ];

            // Get message template
            $template = \App\Models\MessageTemplate::whereNull('company_id')
                ->where('type', 'buyer_invoice')
                ->where('is_active', true)
                ->first();

            if (!$template || !$buyer->phone) {
                Log::warning('Cannot send buyer invoice payment notification', [
                    'has_template' => $template !== null,
                    'has_phone' => $buyer->phone !== null
                ]);
                return;
            }

            $smsMessage = $template->renderSmsTemplate($data);
            Log::info('Sending invoice payment notification to buyer', ['phone' => $buyer->phone, 'message' => $smsMessage]);
            $this->smsService->send($buyer->phone, $smsMessage);
        } catch (\Exception $e) {
            Log::error('Error sending buyer invoice payment notification', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send payment notification to landlord for buyer payment
     */
    protected function sendLandlordBuyerPaymentNotification(Invoice $invoice, $buyer, $unit, $property, $landlord, InvoicePayment $payment): void
    {
        try {
            $data = [
                'amount' => number_format($payment->amount, 0),
                'tenant_name' => $buyer->name,
                'unit_number' => $unit->unit_number,
                'property_name' => $property->name,
                'payment_date' => $payment->paid_on,
            ];

            // Get message template
            $template = \App\Models\MessageTemplate::whereNull('company_id')
                ->where('type', 'landlord_payment_notification')
                ->where('is_active', true)
                ->first();

            if (!$template || !$landlord->phone) {
                Log::warning('Cannot send landlord buyer payment notification', [
                    'has_template' => $template !== null,
                    'has_phone' => $landlord->phone !== null
                ]);
                return;
            }

            $smsMessage = $template->renderSmsTemplate($data);
            Log::info('Sending buyer payment notification to landlord', ['phone' => $landlord->phone, 'message' => $smsMessage]);
            $this->smsService->send($landlord->phone, $smsMessage);
        } catch (\Exception $e) {
            Log::error('Error sending landlord buyer payment notification', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage()
            ]);
        }
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
