<?php

namespace App\Modules\RentCollection\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Invoices\Http\Resources\InvoiceResource;
use App\Modules\Invoices\Models\Invoice;
use App\Modules\RentCollection\Http\Requests\ChargeInvoiceRequest;
use App\Modules\RentCollection\Models\InvoicePaymentAttempt;
use App\Modules\RentCollection\Services\RentCollectionService;
use Illuminate\Http\JsonResponse;

class RentCollectionController extends Controller
{
    public function __construct(protected RentCollectionService $service)
    {
    }

    public function charge(ChargeInvoiceRequest $request, Invoice $invoice): JsonResponse
    {
        $attempt = $this->service->chargeInvoice($invoice, (float) $request->validated('amount'), $request->user());

        return response()->json([
            'data' => [
                'uuid' => $attempt->uuid,
                'status' => $attempt->status,
                'provider' => $attempt->provider,
                'reference' => $attempt->reference,
                'message' => $attempt->message,
            ],
        ], 201);
    }

    public function verify(Invoice $invoice, InvoicePaymentAttempt $attempt): InvoiceResource
    {
        $this->authorize('update', $invoice);
        abort_unless($attempt->invoice_id === $invoice->id, 404);

        $this->service->verifyAttempt($attempt);

        return InvoiceResource::make($invoice->fresh(['lease.tenant', 'lease.unit', 'items', 'payments']));
    }
}
