<?php

namespace App\Modules\Reports\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Reports\Exports\ReportExport;
use App\Modules\Reports\Services\ArAgingReportService;
use App\Modules\Reports\Services\BillingStatusReportService;
use App\Modules\Reports\Services\IncomeStatementReportService;
use App\Modules\Reports\Services\OccupancyReportService;
use App\Modules\Reports\Services\RentRollReportService;
use App\Modules\Reports\Services\SubscriptionStatusReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

/**
 * A single controller for every report - each is a read-only aggregation
 * with an on-screen view and a PDF/Excel/CSV download, not a CRUD
 * resource, so there's no repository/policy/form-request per report the
 * way a domain module would have. Authorization is a single
 * `reports.view` permission checked directly (Spatie Permission's
 * Gate::before hook makes any registered permission name checkable via
 * authorize() without a Policy class - the same pattern Module 4's
 * Settings controller already uses), not a Policy tied to a model, since
 * there's no Eloquent resource here to attach one to.
 */
class ReportController extends Controller
{
    public function index(): View
    {
        $this->authorize('reports.view');

        return view('reports::index');
    }

    public function rentRoll(Request $request, RentRollReportService $service): View
    {
        $this->authorize('reports.view');
        $status = $request->string('status')->toString() ?: null;

        return view('reports::rent-roll', ['rows' => $service->generate($status), 'status' => $status, 'statuses' => RentRollReportService::STATUSES]);
    }

    public function rentRollExport(Request $request, RentRollReportService $service, string $format): Response
    {
        $this->authorize('reports.view');

        $rows = $service->generate($request->string('status')->toString() ?: null);
        $headings = ['Property', 'Building', 'Unit', 'Status', 'Tenant', 'Lease Start', 'Lease End', 'Rent Amount'];

        return $this->export($format, 'rent-roll', $headings, $rows->map(fn ($row) => array_values($row))->all(), 'reports::pdf.rent-roll', ['rows' => $rows]);
    }

    public function occupancy(Request $request, OccupancyReportService $service): View
    {
        $this->authorize('reports.view');
        $status = $request->string('status')->toString() ?: null;

        return view('reports::occupancy', ['rows' => $service->generate($status), 'status' => $status, 'statuses' => OccupancyReportService::STATUSES]);
    }

    public function occupancyExport(Request $request, OccupancyReportService $service, string $format): Response
    {
        $this->authorize('reports.view');

        $rows = $service->generate($request->string('status')->toString() ?: null);
        $headings = ['Property', 'Total Units', 'Occupied', 'Vacant', 'Occupancy Rate (%)'];

        return $this->export($format, 'occupancy', $headings, $rows->map(fn ($row) => array_values($row))->all(), 'reports::pdf.occupancy', ['rows' => $rows]);
    }

    public function incomeStatement(Request $request, IncomeStatementReportService $service): View
    {
        $this->authorize('reports.view');

        [$startDate, $endDate] = $this->resolveDateRange($request);

        return view('reports::income-statement', $service->generate($startDate, $endDate));
    }

    public function incomeStatementExport(Request $request, IncomeStatementReportService $service, string $format): Response
    {
        $this->authorize('reports.view');

        [$startDate, $endDate] = $this->resolveDateRange($request);
        $data = $service->generate($startDate, $endDate);

        $rows = [];
        $rows[] = ['Income', ''];
        foreach ($data['income'] as $account) {
            $rows[] = ["{$account['code']} {$account['name']}", $account['amount']];
        }
        $rows[] = ['Total Income', $data['total_income']];
        $rows[] = ['', ''];
        $rows[] = ['Expenses', ''];
        foreach ($data['expenses'] as $account) {
            $rows[] = ["{$account['code']} {$account['name']}", $account['amount']];
        }
        $rows[] = ['Total Expenses', $data['total_expenses']];
        $rows[] = ['', ''];
        $rows[] = ['Net Income', $data['net_income']];

        return $this->export($format, 'income-statement', ['Account', 'Amount'], $rows, 'reports::pdf.income-statement', $data);
    }

    public function subscriptionStatus(SubscriptionStatusReportService $service): View
    {
        abort_unless(request()->user()->isSaasOwner(), 403);
        $this->authorize('reports.view');

        return view('reports::subscription-status', ['rows' => $service->generate()]);
    }

    public function billingStatus(BillingStatusReportService $service): View
    {
        abort_unless(request()->user()->isSaasOwner(), 403);
        $this->authorize('reports.view');

        return view('reports::billing-status', ['rows' => $service->generate()]);
    }

    public function arAging(Request $request, ArAgingReportService $service): View
    {
        $this->authorize('reports.view');
        $status = $request->string('status')->toString() ?: null;

        return view('reports::ar-aging', [...$service->generate($status), 'status' => $status, 'statuses' => ArAgingReportService::STATUSES]);
    }

    public function arAgingExport(Request $request, ArAgingReportService $service, string $format): Response
    {
        $this->authorize('reports.view');

        $data = $service->generate($request->string('status')->toString() ?: null);

        $rows = [];
        $bucketLabels = ['current' => 'Current', '1_30' => '1-30 Days', '31_60' => '31-60 Days', '61_90' => '61-90 Days', 'over_90' => 'Over 90 Days'];
        foreach ($data['buckets'] as $key => $invoices) {
            foreach ($invoices as $invoice) {
                $rows[] = [$bucketLabels[$key], $invoice['invoice_number'], $invoice['tenant'], $invoice['due_date'], $invoice['days_overdue'], $invoice['balance']];
            }
        }
        $rows[] = ['Grand Total', '', '', '', '', $data['grand_total']];

        return $this->export($format, 'ar-aging', ['Bucket', 'Invoice #', 'Tenant', 'Due Date', 'Days Overdue', 'Balance'], $rows, 'reports::pdf.ar-aging', $data);
    }

    protected function resolveDateRange(Request $request): array
    {
        $startDate = $request->filled('start_date') ? Carbon::parse($request->string('start_date')) : now()->startOfMonth();
        $endDate = $request->filled('end_date') ? Carbon::parse($request->string('end_date')) : now()->endOfMonth();

        return [$startDate, $endDate];
    }

    protected function export(string $format, string $filename, array $headings, array $rows, string $pdfView, array $pdfData): Response
    {
        abort_unless(in_array($format, ['pdf', 'xlsx', 'csv'], true), 404);

        if ($format === 'pdf') {
            return Pdf::loadView($pdfView, $pdfData)->download("{$filename}.pdf");
        }

        return Excel::download(
            new ReportExport($rows, $headings, $filename),
            "{$filename}.{$format}",
            $format === 'csv' ? ExcelFormat::CSV : ExcelFormat::XLSX,
        );
    }
}
