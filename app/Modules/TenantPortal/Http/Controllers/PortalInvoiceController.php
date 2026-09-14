<?php

namespace App\Modules\TenantPortal\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Invoices\Models\Invoice;
use App\Modules\RentCollection\Services\RentCollectionService;
use App\Modules\TenantPortal\Http\Requests\PortalPayInvoiceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PortalInvoiceController extends Controller
{
    public function __construct(protected RentCollectionService $rentCollection)
    {
    }

    public function index(): View
    {
        $tenant = Auth::guard('tenant')->user();

        return view('tenant-portal::invoices.index', [
            'invoices' => $tenant->invoices()->with('lease.unit.building.property')->latest('due_date')->get(),
        ]);
    }

    public function show(Invoice $invoice): View
    {
        $tenant = Auth::guard('tenant')->user();
        abort_unless($invoice->lease->tenant_id === $tenant->id, 403);

        return view('tenant-portal::invoices.show', [
            'invoice' => $invoice->load(['lease.unit.building.property', 'items', 'payments', 'paymentAttempts']),
        ]);
    }

    /**
     * Reuses RentCollectionService::chargeInvoice() with a null
     * initiator - that parameter is already nullable precisely because
     * not every charge is staff-initiated, so no change to Module 18's
     * own code was needed for a tenant to pay their own invoice through
     * the portal. See ARCHITECTURE.md §19.
     */
    public function pay(PortalPayInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->rentCollection->chargeInvoice($invoice, (float) $request->validated('amount'), null);

        return redirect()->route('portal.invoices.show', $invoice)
            ->with('success', 'Payment initiated - check back shortly for confirmation.');
    }
}
