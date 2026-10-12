<?php

namespace App\Modules\BuyerPortal\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Invoices\Models\Invoice;
use App\Modules\Sales\Models\Sale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * The buyer's web portal - their purchases, instalment schedule and
 * invoices; the same data the buyer mobile app shows.
 */
class BuyerPortalController extends Controller
{
    public function dashboard(): View
    {
        $buyer = Auth::guard('buyer')->user();
        $sales = $buyer->sales()->with('unit.building.property', 'installments')->latest('sale_date')->get();
        $outstanding = $this->buyerInvoices()->whereIn('status', ['sent', 'partially_paid', 'overdue'])->get();
        $nextInstallment = $sales->flatMap->installments
            ->filter(fn ($i) => $i->status !== 'paid')
            ->sortBy('due_date')
            ->first();

        return view('buyer-portal::dashboard', [
            'sales' => $sales,
            'totalPrice' => (float) $sales->sum('total_price'),
            'totalPaid' => (float) $sales->sum(fn (Sale $sale) => $sale->total_paid),
            'outstandingBalance' => (float) $outstanding->sum(fn (Invoice $invoice) => $invoice->balanceDue()),
            'outstandingCount' => $outstanding->count(),
            'nextInstallment' => $nextInstallment,
        ]);
    }

    public function purchases(): View
    {
        return view('buyer-portal::purchases.index', [
            'sales' => Auth::guard('buyer')->user()->sales()->with('unit.building.property')->latest('sale_date')->get(),
        ]);
    }

    public function purchase(Sale $sale): View
    {
        abort_unless($sale->new_buyer_id === Auth::guard('buyer')->id(), 403);

        return view('buyer-portal::purchases.show', [
            'sale' => $sale->load(['unit.building.property', 'installments' => fn ($q) => $q->orderBy('installment_number')]),
            'invoices' => $sale->invoices()->latest('due_date')->get(),
        ]);
    }

    public function invoices(Request $request): View
    {
        $status = $request->query('status');

        return view('buyer-portal::invoices.index', [
            'invoices' => $this->buyerInvoices()
                ->with('sale.unit.building.property')
                ->when(is_string($status) && $status !== '', fn ($q) => $q->where('status', $status))
                ->latest('due_date')
                ->paginate(15)
                ->withQueryString(),
            'status' => $status,
        ]);
    }

    public function invoice(Invoice $invoice): View
    {
        abort_unless($invoice->sale?->new_buyer_id === Auth::guard('buyer')->id(), 403);

        return view('buyer-portal::invoices.show', ['invoice' => $invoice->load(['sale.unit.building.property', 'items', 'payments'])]);
    }

    protected function buyerInvoices(): Builder
    {
        $buyerId = Auth::guard('buyer')->id();

        return Invoice::query()->whereHas('sale', fn (Builder $q) => $q->where('new_buyer_id', $buyerId));
    }
}
