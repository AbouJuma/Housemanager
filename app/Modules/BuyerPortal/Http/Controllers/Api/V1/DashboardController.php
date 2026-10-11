<?php

namespace App\Modules\BuyerPortal\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Invoices\Models\Invoice;
use App\Modules\Sales\Http\Resources\SaleResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Purchase summary for the buyer mobile dashboard - shape mirrors
 * App\Modules\TenantPortal\Http\Controllers\PortalDashboardController's
 * "active leases + outstanding balance" idea, applied to sales/
 * installments instead of leases/rent invoices.
 */
class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $buyer = $request->user();
        $sales = $buyer->sales()->with('unit.building.property')->get();

        $outstandingInvoices = Invoice::whereHas('sale', fn ($query) => $query->where('new_buyer_id', $buyer->id))
            ->whereIn('status', ['sent', 'partially_paid', 'overdue'])
            ->get();

        return response()->json([
            'sales' => SaleResource::collection($sales),
            'total_purchases' => $sales->count(),
            'total_spent' => $sales->sum('total_price'),
            'outstanding_balance' => $outstandingInvoices->sum(fn ($invoice) => $invoice->balanceDue()),
            'outstanding_invoice_count' => $outstandingInvoices->count(),
        ]);
    }
}
