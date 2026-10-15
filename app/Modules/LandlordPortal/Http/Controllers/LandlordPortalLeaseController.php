<?php

namespace App\Modules\LandlordPortal\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\LandlordPortal\Http\Requests\LandlordLeaseRequest;
use App\Modules\LandlordPortal\Support\LandlordAccess;
use App\Modules\Leases\Services\LeaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * A landlord renting out one of their vacant units: the same lease form
 * staff use (rent and end date worked out from the unit's price, billing
 * cycle and start date), limited to the landlord's units and tenants.
 */
class LandlordPortalLeaseController extends Controller
{
    public function __construct(protected LeaseService $leases) {}

    public function create(Request $request): View
    {
        $landlord = Auth::guard('landlord')->user();
        LandlordAccess::authorize($landlord, 'leases.create');

        return view('landlord-portal::leases.create', [
            'tenants' => $landlord->tenantsQuery()->where('status', '!=', 'blacklisted')->orderBy('name')->get(['id', 'uuid', 'name']),
            'units' => $landlord->unitsQuery()->where('status', 'vacant')->with('building.property')->orderBy('unit_number')->get(),
            'selectedUnitUuid' => $request->query('unit'),
            'addTenantUrl' => LandlordAccess::can($landlord, 'tenants.create') ? route('landlord-portal.tenants.create') : null,
        ]);
    }

    public function store(LandlordLeaseRequest $request): RedirectResponse
    {
        $lease = $this->leases->create($request->validated());

        return redirect()->route('landlord-portal.properties.show', $lease->unit->building->property)
            ->with('success', __('Lease created - unit :unit is now rented to :tenant.', ['unit' => $lease->unit->unit_number, 'tenant' => $lease->tenant->name]));
    }
}
