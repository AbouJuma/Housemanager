<?php

namespace App\Modules\LandlordPortal\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\LandlordPortal\Support\LandlordInvoices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/** Who still owes the landlord - rent and sale invoices on their units. */
class LandlordPortalInvoiceController extends Controller
{
    public function index(Request $request): View
    {
        [$query, $status] = LandlordInvoices::query(
            Auth::guard('landlord')->user(),
            $request->query('status'),
            $request->query('period'),
            $request->query('from'),
            $request->query('to'),
        );

        $invoices = (clone $query)->orderBy('due_date')->paginate(25)->withQueryString();

        return view('landlord-portal::invoices.index', [
            'invoices' => $invoices,
            'rows' => $invoices->getCollection()->map(fn ($invoice) => LandlordInvoices::row($invoice)),
            'summary' => LandlordInvoices::summary($query),
            'status' => $status,
            'period' => $request->query('period'),
            'from' => $request->query('from'),
            'to' => $request->query('to'),
        ]);
    }
}
