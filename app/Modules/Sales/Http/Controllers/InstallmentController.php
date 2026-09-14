<?php

namespace App\Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Sales\Http\Requests\RecordPaymentRequest;
use App\Modules\Sales\Models\Installment;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Services\SaleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class InstallmentController extends Controller
{
    public function __construct(protected SaleService $service)
    {
    }

    public function show(Sale $sale, string $installment): View
    {
        $this->authorize('view', $sale);

        $installment = Installment::where('uuid', $installment)->where('sale_id', $sale->id)->firstOrFail();

        return view('sales::installments.show', [
            'sale' => $sale,
            'installment' => $installment,
        ]);
    }

    public function recordPayment(RecordPaymentRequest $request, Sale $sale, string $installment): RedirectResponse
    {
        $this->authorize('update', $sale);

        $installment = Installment::where('uuid', $installment)->where('sale_id', $sale->id)->firstOrFail();
        $this->service->recordPayment($installment, $request->validated()['amount']);

        return redirect()->route('sales.show', $sale)->with('success', 'Payment recorded successfully.');
    }

    public function markOverdue(Sale $sale, string $installment): RedirectResponse
    {
        $this->authorize('update', $sale);

        $installment = Installment::where('uuid', $installment)->where('sale_id', $sale->id)->firstOrFail();
        $installment->markAsOverdue();

        return redirect()->route('sales.show', $sale)->with('success', 'Installment marked as overdue.');
    }
}
