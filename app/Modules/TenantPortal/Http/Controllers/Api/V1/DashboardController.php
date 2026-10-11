<?php

namespace App\Modules\TenantPortal\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Leases\Http\Resources\LeaseResource;
use App\Modules\Maintenance\Http\Resources\MaintenanceRequestResource;
use App\Modules\Maintenance\Models\MaintenanceRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * JSON mirror of App\Modules\TenantPortal\Http\Controllers\
 * PortalDashboardController - same queries, same shape, for the mobile
 * app. $request->user() is already guaranteed to be a Tenant by the
 * 'ensure.tenant-token' middleware on this route group.
 */
class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenant = $request->user();

        $activeLeases = $tenant->leases()->where('leases.status', 'active')->with('unit.building.property')->get();
        $outstandingInvoices = $tenant->invoices()->whereIn('invoices.status', ['sent', 'partially_paid', 'overdue'])->with('lease')->get();
        $recentMaintenanceRequests = MaintenanceRequest::where('tenant_id', $tenant->id)->latest('reported_date')->limit(5)->get();

        return response()->json([
            'active_leases' => LeaseResource::collection($activeLeases),
            'outstanding_balance' => $outstandingInvoices->sum(fn ($invoice) => $invoice->balanceDue()),
            'outstanding_invoice_count' => $outstandingInvoices->count(),
            'recent_maintenance_requests' => MaintenanceRequestResource::collection($recentMaintenanceRequests),
        ]);
    }
}
