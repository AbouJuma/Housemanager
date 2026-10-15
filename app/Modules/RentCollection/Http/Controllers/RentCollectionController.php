<?php

namespace App\Modules\RentCollection\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Invoices\Models\Invoice;
use App\Modules\RentCollection\Http\Requests\ChargeInvoiceRequest;
use App\Modules\RentCollection\Models\InvoicePaymentAttempt;
use App\Modules\RentCollection\Services\RentCollectionService;
use Illuminate\Http\RedirectResponse;

class RentCollectionController extends Controller
{
    public function __construct(protected RentCollectionService $service)
    {
    }

    public function charge(ChargeInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->service->chargeInvoice($invoice, (float) $request->validated('amount'), $request->user());

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Payment initiated - check back shortly for confirmation, or use "Check Status" once the tenant has approved it.');
    }

    public function verify(Invoice $invoice, InvoicePaymentAttempt $attempt): RedirectResponse
    {
        $this->authorize('update', $invoice);
        abort_unless(same_id($attempt->invoice_id, $invoice->id), 404);

        $this->service->verifyAttempt($attempt);

        return redirect()->route('invoices.show', $invoice)->with('success', 'Payment status refreshed.');
    }
}
