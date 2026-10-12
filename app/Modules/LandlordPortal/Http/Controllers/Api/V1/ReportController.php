<?php

namespace App\Modules\LandlordPortal\Http\Controllers\Api\V1;

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
        [$from, $to] = LandlordReportService::period($request->query('from'), $request->query('to'));

        return response()->json(['data' => $this->reports->profitAndLoss($request->user(), $from, $to)]);
    }

    public function units(Request $request): JsonResponse
    {
        $status = $request->query('status');

        return response()->json(['data' => $this->reports->units($request->user(), is_string($status) ? $status : null)]);
    }
}
