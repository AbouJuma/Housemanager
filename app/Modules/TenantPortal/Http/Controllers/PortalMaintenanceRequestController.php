<?php

namespace App\Modules\TenantPortal\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Maintenance\Models\MaintenanceRequest;
use App\Modules\Maintenance\Services\MaintenanceRequestService;
use App\Modules\TenantPortal\Http\Requests\PortalStoreMaintenanceRequestRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PortalMaintenanceRequestController extends Controller
{
    public function __construct(protected MaintenanceRequestService $service) {}

    public function index(): View
    {
        $tenant = Auth::guard('tenant')->user();

        return view('tenant-portal::maintenance-requests.index', [
            'requests' => MaintenanceRequest::where('tenant_id', $tenant->id)->with('property', 'vendor')->latest('reported_date')->get(),
        ]);
    }

    public function create(): View
    {
        $tenant = Auth::guard('tenant')->user();

        return view('tenant-portal::maintenance-requests.create', [
            'leases' => $tenant->leases()->where('status', 'active')->with('unit.building.property')->get(),
        ]);
    }

    public function store(PortalStoreMaintenanceRequestRequest $request): RedirectResponse
    {
        $tenant = Auth::guard('tenant')->user();
        $lease = $tenant->leases()->with('unit')->findOrFail($request->validated('lease_id'));

        $this->service->create([
            ...$request->validated(),
            'company_id' => $tenant->company_id,
            'property_id' => $lease->unit->building->property_id,
            'unit_id' => $lease->unit_id,
            'tenant_id' => $tenant->id,
            'reported_date' => now()->toDateString(),
        ]);

        return redirect()->route('portal.maintenance-requests.index')->with('success', 'Maintenance request submitted.');
    }
}
