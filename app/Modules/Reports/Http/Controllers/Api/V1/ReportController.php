<?php

namespace App\Modules\Reports\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Reports\Services\OccupancyReportService;
use App\Modules\Reports\Services\RentRollReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * JSON mirror of the two reports the mobile app surfaces (staff area) -
 * same `reports.view` permission and same aggregation services the web
 * Reports module (App\Modules\Reports\Http\Controllers\ReportController)
 * uses, just returned as data instead of a Blade view. Every other
 * report (Income Statement, AR Aging, Billing/Subscription Status)
 * stays web-only for now.
 */
class ReportController extends Controller
{
    public function rentRoll(Request $request, RentRollReportService $service): JsonResponse
    {
        $this->authorize('reports.view');

        return response()->json([
            'data' => $service->generate($request->string('status')->toString() ?: null)->values(),
            'statuses' => RentRollReportService::STATUSES,
        ]);
    }

    public function occupancy(Request $request, OccupancyReportService $service): JsonResponse
    {
        $this->authorize('reports.view');

        return response()->json([
            'data' => $service->generate($request->string('status')->toString() ?: null)->values(),
            'statuses' => OccupancyReportService::STATUSES,
        ]);
    }
}
