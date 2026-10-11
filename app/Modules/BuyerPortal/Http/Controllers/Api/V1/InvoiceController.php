<?php

namespace App\Modules\BuyerPortal\Http\Controllers\Api\V1;

use App\Core\Http\Concerns\HasListFilters;
use App\Http\Controllers\Controller;
use App\Modules\Invoices\Http\Resources\InvoiceResource;
use App\Modules\Invoices\Models\Invoice;
use App\Modules\RentCollection\Services\RentCollectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Mirrors App\Modules\TenantPortal\Http\Controllers\Api\V1\
 * InvoiceController exactly, scoped to sale-linked invoices - pay()
 * reuses the same RentCollectionService::chargeInvoice(), which is
 * already generic over any Invoice regardless of lease vs. sale.
 * index() is paginated (15/page) with optional ?status=&from=&to=
 * (date range against due_date).
 */
class InvoiceController extends Controller
{
    use HasListFilters;

    public function __construct(protected RentCollectionService $rentCollection) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $buyer = $request->user();

        $query = Invoice::whereHas('sale', fn ($q) => $q->where('new_buyer_id', $buyer->id))
            ->with('sale.unit.building.property');
        $query = $this->applyStatusFilter($query, $request);
        $query = $this->applyDateRangeFilter($query, $request, 'due_date');

        return InvoiceResource::collection($query->latest('due_date')->paginate(15)->withQueryString());
    }

    public function show(Request $request, Invoice $invoice): InvoiceResource
    {
        abort_unless($invoice->sale?->new_buyer_id === $request->user()->id, 403);

        return InvoiceResource::make($invoice->load(['sale.unit.building.property', 'items', 'payments', 'paymentAttempts']));
    }

    public function pay(Request $request, Invoice $invoice): JsonResponse
    {
        abort_unless($invoice->sale?->new_buyer_id === $request->user()->id, 403);

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
