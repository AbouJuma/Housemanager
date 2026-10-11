<?php

namespace App\Modules\Invoices\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Invoices\Http\Requests\RecordInvoicePaymentRequest;
use App\Modules\Invoices\Http\Requests\StoreInvoiceRequest;
use App\Modules\Invoices\Http\Requests\UpdateInvoiceRequest;
use App\Modules\Invoices\Http\Resources\InvoiceResource;
use App\Modules\Invoices\Models\Invoice;
use App\Modules\Invoices\Services\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InvoiceController extends Controller
{
    public function __construct(protected InvoiceService $service)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Invoice::class);

        return InvoiceResource::collection(
            $this->service->paginate(
                $request->string('search')->toString() ?: null,
                $request->string('status')->toString() ?: null,
                from: $request->string('from')->toString() ?: null,
                to: $request->string('to')->toString() ?: null,
            )
        );
    }

    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        $invoice = $this->service->create([...$request->validated(), 'company_id' => $request->user()->company_id]);

        return InvoiceResource::make($invoice->load(['lease.tenant', 'lease.unit', 'items']))->response()->setStatusCode(201);
    }

    public function show(Invoice $invoice): InvoiceResource
    {
        $this->authorize('view', $invoice);

        return InvoiceResource::make($invoice->load(['lease.tenant', 'lease.unit.building.property', 'items', 'payments']));
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice): InvoiceResource
    {
        $invoice = $this->service->update($invoice, $request->validated());

        return InvoiceResource::make($invoice->load(['lease.tenant', 'lease.unit', 'items']));
    }

    public function destroy(Invoice $invoice): JsonResponse
    {
        $this->authorize('delete', $invoice);

        $this->service->delete($invoice);

        return response()->json(null, 204);
    }

    public function send(Invoice $invoice): InvoiceResource
    {
        $this->authorize('update', $invoice);

        return InvoiceResource::make($this->service->send($invoice));
    }

    public function cancel(Invoice $invoice): InvoiceResource
    {
        $this->authorize('update', $invoice);

        return InvoiceResource::make($this->service->cancel($invoice));
    }

    public function recordPayment(RecordInvoicePaymentRequest $request, Invoice $invoice): InvoiceResource
    {
        $this->service->recordPayment($invoice, [...$request->validated(), 'recorded_by_id' => $request->user()->id]);

        return InvoiceResource::make($invoice->fresh(['lease.tenant', 'lease.unit', 'items', 'payments']));
    }
}
