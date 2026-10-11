<?php

namespace App\Modules\TenantPortal\Http\Controllers\Api\V1;

use App\Core\Http\Concerns\HasListFilters;
use App\Http\Controllers\Controller;
use App\Modules\Invoices\Http\Resources\InvoiceResource;
use App\Modules\Invoices\Models\Invoice;
use App\Modules\RentCollection\Services\RentCollectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * JSON mirror of App\Modules\TenantPortal\Http\Controllers\
 * PortalInvoiceController. pay() reuses RentCollectionService::
 * chargeInvoice() with a null initiator, same as the web portal - see
 * that controller's docblock for why no change to Module 18 was needed.
 * index() is paginated (15/page) with optional ?status=&from=&to=
 * (date range against due_date).
 */
class InvoiceController extends Controller
{
    use HasListFilters;

    public function __construct(protected RentCollectionService $rentCollection) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        // 'invoices.status'/'invoices.due_date', not unqualified - this
        // is a HasManyThrough (Tenant -> Lease -> Invoice) and both
        // joined tables have their own status column, the same
        // ambiguous-column trap documented elsewhere in this app
        // (Module 26/27's dashboard controllers).
        $query = $request->user()->invoices()->with('lease.unit.building.property');
        $query = $this->applyStatusFilter($query, $request, 'invoices.status');
        $query = $this->applyDateRangeFilter($query, $request, 'invoices.due_date');

        return InvoiceResource::collection($query->latest('invoices.due_date')->paginate(15)->withQueryString());
    }

    public function show(Request $request, Invoice $invoice): InvoiceResource
    {
        abort_unless($invoice->lease?->tenant_id === $request->user()->id, 403);

        return InvoiceResource::make($invoice->load(['lease.unit.building.property', 'items', 'payments', 'paymentAttempts']));
    }

    public function pay(Request $request, Invoice $invoice): JsonResponse
    {
        abort_unless($invoice->lease?->tenant_id === $request->user()->id, 403);

        $validated = $request->validate(['amount' => ['required', 'numeric', 'min:0.01']]);

        $attempt = $this->rentCollection->chargeInvoice($invoice, (float) $validated['amount'], null);

        return response()->json([
            'message' => 'Payment initiated - check back shortly for confirmation.',
            'payment_attempt' => [
                'uuid' => $attempt->uuid,
                'provider' => $attempt->provider,
                'amount' => $attempt->amountMajor(),
                'status' => $attempt->status,
            ],
        ], 202);
    }
}
