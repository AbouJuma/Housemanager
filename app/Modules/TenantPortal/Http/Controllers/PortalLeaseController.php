<?php

namespace App\Modules\TenantPortal\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Leases\Models\Lease;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PortalLeaseController extends Controller
{
    public function index(): View
    {
        $tenant = Auth::guard('tenant')->user();

        return view('tenant-portal::leases.index', [
            'leases' => $tenant->leases()->with('unit.building.property')->latest('start_date')->get(),
        ]);
    }

    public function show(Lease $lease): View
    {
        $tenant = Auth::guard('tenant')->user();
        abort_unless($lease->tenant_id === $tenant->id, 403);

        return view('tenant-portal::leases.show', [
            'lease' => $lease->load(['unit.building.property', 'invoices']),
        ]);
    }
}
