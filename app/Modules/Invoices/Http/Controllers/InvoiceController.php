<?php

namespace App\Modules\Invoices\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Invoices\Http\Requests\RecordInvoicePaymentRequest;
use App\Modules\Invoices\Http\Requests\StoreInvoiceRequest;
use App\Modules\Invoices\Http\Requests\UpdateInvoiceRequest;
use App\Modules\Integrations\Models\Integration;
use App\Modules\Invoices\Models\Invoice;
use App\Modules\Invoices\Services\InvoiceService;
use App\Modules\Leases\Models\Lease;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function __construct(protected InvoiceService $service)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Invoice::class);

        $invoices = $this->service->paginate(
            $request->string('search')->toString() ?: null,
            $request->string('status')->toString() ?: null,
        );

        return view('invoices::index', compact('invoices'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Invoice::class);

        return view('invoices::create', [
            'leases' => $this->leaseOptions(),
            'selectedLeaseUuid' => $request->query('lease_id'),
        ]);
    }

    public function store(StoreInvoiceRequest $request): RedirectResponse
    {
        $invoice = $this->service->create([...$request->validated(), 'company_id' => $request->user()->company_id]);

        return redirect()->route('invoices.show', $invoice)->with('success', "Invoice \"{$invoice->invoice_number}\" created.");
    }

    public function show(Invoice $invoice): View
    {
        $this->authorize('view', $invoice);

        return view('invoices::show', [
            'invoice' => $invoice->load(['lease.tenant', 'lease.unit.building.property', 'sale.buyer', 'sale.unit.building.property', 'items', 'payments.recordedBy', 'paymentAttempts']),
            // Gates the "Charge Online" section - a company with no
            // active payment integration has nothing for it to call
            // (RentCollectionService::chargeInvoice() would just reject
            // it), so there's no point rendering a form that can only fail.
            'hasActivePaymentProvider' => Integration::query()
                ->forCompany($invoice->company_id)
                ->where('type', 'payment')
                ->where('is_active', true)
                ->exists(),
        ]);
    }

    public function edit(Invoice $invoice): View
    {
        $this->authorize('update', $invoice);

        return view('invoices::edit', [
            'invoice' => $invoice->load(['lease.tenant', 'lease.unit.building.property', 'items']),
        ]);
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->service->update($invoice, $request->validated());

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice updated.');
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $this->authorize('delete', $invoice);

        $this->service->delete($invoice);

        return redirect()->route('invoices.index')->with('success', "Invoice \"{$invoice->invoice_number}\" deleted.");
    }

    public function send(Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        $this->service->send($invoice);

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice sent.');
    }

    public function cancel(Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        $this->service->cancel($invoice);

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice cancelled.');
    }

    public function recordPayment(RecordInvoicePaymentRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->service->recordPayment($invoice, [...$request->validated(), 'recorded_by_id' => $request->user()->id]);

        return redirect()->route('invoices.show', $invoice)->with('success', 'Payment recorded.');
    }

    public function downloadPDF(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $invoice->load(['lease.tenant', 'lease.unit.building.property', 'sale.buyer', 'items']);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('invoices::pdf', compact('invoice'));

        return $pdf->download("invoice-{$invoice->invoice_number}.pdf");
    }

    protected function leaseOptions(): \Illuminate\Support\Collection
    {
        // Every lease regardless of status - a company may legitimately
        // need to invoice a lease that has since ended (a final month,
        // an early-termination fee), not just currently active ones.
        return Lease::query()->with(['tenant', 'unit'])->orderByDesc('id')->get();
    }
}
