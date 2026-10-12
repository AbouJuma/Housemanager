<?php

namespace App\Modules\LandlordPortal\Http\Controllers;

use App\Core\Support\Period;
use App\Http\Controllers\Controller;
use App\Modules\LandlordPortal\Services\LandlordReportService;
use App\Modules\LandlordPortal\Support\LandlordAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LandlordPortalReportController extends Controller
{
    public function __construct(protected LandlordReportService $reports) {}

    public function profitLoss(Request $request): View
    {
        $landlord = Auth::guard('landlord')->user();
        LandlordAccess::authorize($landlord, 'reports.view');
        [$from, $to, $period] = Period::resolve($request->query('period'), $request->query('from'), $request->query('to'));

        return view('landlord-portal::reports.profit-loss', [
            'report' => $this->reports->profitAndLoss($landlord, $from, $to, $request->query('group')),
            'period' => $period,
        ]);
    }

    public function units(Request $request): View
    {
        $landlord = Auth::guard('landlord')->user();
        LandlordAccess::authorize($landlord, 'properties.viewAny');
        $status = $request->query('status');

        return view('landlord-portal::reports.units', [
            'report' => $this->reports->units($landlord, is_string($status) ? $status : null),
            'status' => $status,
        ]);
    }
}
