<?php

namespace App\Modules\LandlordPortal\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\LandlordPortal\Http\Requests\LandlordPropertyRequest;
use App\Modules\LandlordPortal\Services\LandlordManagementService;
use App\Modules\LandlordPortal\Support\LandlordAccess;
use App\Modules\Properties\Models\Property;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LandlordPortalPropertyController extends Controller
{
    public function __construct(protected LandlordManagementService $service) {}

    public function index(): View
    {
        $landlord = Auth::guard('landlord')->user();
        LandlordAccess::authorize($landlord, 'properties.viewAny');

        return view('landlord-portal::properties.index', [
            'properties' => $landlord->properties()->withCount([
                'buildings',
                'units',
                'units as occupied_units_count' => fn ($query) => $query->where('units.status', 'occupied'),
                'units as vacant_units_count' => fn ($query) => $query->where('units.status', 'vacant'),
            ])->latest()->get(),
            'canCreate' => LandlordAccess::can($landlord, 'properties.create'),
        ]);
    }

    public function create(): View
    {
        LandlordAccess::authorize(Auth::guard('landlord')->user(), 'properties.create');

        return view('landlord-portal::properties.create');
    }

    public function store(LandlordPropertyRequest $request): RedirectResponse
    {
        $property = $this->service->createProperty($request->user(), $request->validated());

        return redirect()->route('landlord-portal.properties.show', $property)
            ->with('success', __('Property ":name" added.', ['name' => $property->name]));
    }

    public function show(Property $property): View
    {
        $landlord = Auth::guard('landlord')->user();
        abort_unless($property->landlord_id === $landlord->id, 403);
        LandlordAccess::authorize($landlord, 'properties.viewAny');

        return view('landlord-portal::properties.show', [
            'property' => $property->load('buildings.units.leases.tenant', 'buildings.units.sales.buyer'),
            'canUpdate' => LandlordAccess::can($landlord, 'properties.update'),
            'canAddUnits' => LandlordAccess::can($landlord, 'units.create'),
            'canEditUnits' => LandlordAccess::can($landlord, 'units.update'),
        ]);
    }

    public function edit(Property $property): View
    {
        $landlord = Auth::guard('landlord')->user();
        abort_unless($property->landlord_id === $landlord->id, 403);
        LandlordAccess::authorize($landlord, 'properties.update');

        return view('landlord-portal::properties.edit', compact('property'));
    }

    public function update(LandlordPropertyRequest $request, Property $property): RedirectResponse
    {
        $this->service->updateProperty($property, $request->validated());

        return redirect()->route('landlord-portal.properties.show', $property)->with('success', __('Property updated.'));
    }
}
