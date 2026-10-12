<?php

namespace App\Modules\LandlordPortal\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\LandlordPortal\Support\LandlordInvoices;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Invoices on the landlord's units for the app - pending by default;
 * ?status=pending|overdue|partially_paid|paid|all, ?period= or ?from=&to=
 * (due date).
 */
class InvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        [$query, $status] = LandlordInvoices::query($request->user(), $request->query('status'), $request->query('period'), $request->query('from'), $request->query('to'));
        $page = (clone $query)->orderBy('due_date')->paginate(15)->withQueryString();

        return response()->json([
            'data' => $page->getCollection()->map(fn ($invoice) => LandlordInvoices::row($invoice))->values(),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
            'summary' => ['status' => $status, ...LandlordInvoices::summary($query)],
        ]);
    }
}
