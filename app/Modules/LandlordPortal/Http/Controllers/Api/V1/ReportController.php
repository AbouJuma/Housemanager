<?php

namespace App\Modules\LandlordPortal\Http\Controllers\Api\V1;

use App\Core\Support\Period;
use App\Http\Controllers\Controller;
use App\Modules\LandlordPortal\Services\LandlordReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** The landlord's own reports in the app. */
class ReportController extends Controller
{
    public function __construct(protected LandlordReportService $reports) {}

    public function profitLoss(Request $request): JsonResponse
    {
        $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date']]);
        // ?period=today|this_week|this_month|... or ?from=&to=, and ?group=day|week|month.
        [$from, $to, $period] = Period::resolve($request->query('period'), $request->query('from'), $request->query('to'));

        return response()->json(['data' => ['period' => $period, ...$this->reports->profitAndLoss($request->user(), $from, $to, $request->query('group'))]]);
    }

    public function units(Request $request): JsonResponse
    {
        $status = $request->query('status');

        return response()->json(['data' => $this->reports->units($request->user(), is_string($status) ? $status : null)]);
    }
}
