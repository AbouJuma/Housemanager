<?php

namespace App\Modules\TenantPortal\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Maintenance\Models\MaintenanceRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PortalDashboardController extends Controller
{
    public function index(): View
    {
        $tenant = Auth::guard('tenant')->user();

        $activeLeases = $tenant->leases()->where('leases.status', 'active')->with('unit.building.property')->get();
        $outstandingInvoices = $tenant->invoices()->whereIn('invoices.status', ['sent', 'partially_paid', 'overdue'])->with('lease')->get();
        $recentMaintenanceRequests = MaintenanceRequest::where('tenant_id', $tenant->id)->latest('reported_date')->limit(5)->get();

        return view('tenant-portal::dashboard', [
            'tenant' => $tenant,
            'activeLeases' => $activeLeases,
            'outstandingInvoices' => $outstandingInvoices,
            'outstandingBalance' => $outstandingInvoices->sum(fn ($invoice) => $invoice->balanceDue()),
            'recentMaintenanceRequests' => $recentMaintenanceRequests,
        ]);
    }
}
