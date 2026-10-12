<?php

namespace App\Modules\LandlordPortal\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\LandlordPortal\Http\Requests\LandlordBuildingRequest;
use App\Modules\LandlordPortal\Http\Requests\LandlordUnitRequest;
use App\Modules\LandlordPortal\Services\LandlordManagementService;
use App\Modules\LandlordPortal\Support\LandlordAccess;
use App\Modules\Properties\Models\Building;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/** Landlords adding buildings and units to their own properties. */
class LandlordPortalUnitController extends Controller
{
    public function __construct(protected LandlordManagementService $service) {}

    public function createBuilding(Property $property): View
    {
        $this->own($property);
        LandlordAccess::authorize(Auth::guard('landlord')->user(), 'units.create');

        return view('landlord-portal::buildings.create', compact('property'));
    }

    public function storeBuilding(LandlordBuildingRequest $request, Property $property): RedirectResponse
    {
        $building = $this->service->createBuilding($property, $request->validated());

        return redirect()->route('landlord-portal.units.create', $building)
            ->with('success', __('Building ":name" added - now add its units.', ['name' => $building->name]));
    }

    public function createUnit(Building $building): View
    {
        $this->own($building->property);
        LandlordAccess::authorize(Auth::guard('landlord')->user(), 'units.create');

        return view('landlord-portal::units.form', ['building' => $building->load('property'), 'unit' => null]);
    }

    public function storeUnit(LandlordUnitRequest $request, Building $building): RedirectResponse
    {
        $unit = $this->service->createUnit($building, $request->validated());

        // "Save & add another" keeps the landlord on the form.
        if ($request->boolean('add_another')) {
            return redirect()->route('landlord-portal.units.create', $building)
                ->with('success', __('Unit :number added.', ['number' => $unit->unit_number]));
        }

        return redirect()->route('landlord-portal.properties.show', $building->property)
            ->with('success', __('Unit :number added.', ['number' => $unit->unit_number]));
    }

    public function editUnit(Unit $unit): View
    {
        $this->own($unit->building->property);
        LandlordAccess::authorize(Auth::guard('landlord')->user(), 'units.update');

        return view('landlord-portal::units.form', ['building' => $unit->building->load('property'), 'unit' => $unit]);
    }

    public function updateUnit(LandlordUnitRequest $request, Unit $unit): RedirectResponse
    {
        $this->service->updateUnit($unit, $request->validated());

        return redirect()->route('landlord-portal.properties.show', $unit->building->property)
            ->with('success', __('Unit :number updated.', ['number' => $unit->unit_number]));
    }

    protected function own(?Property $property): void
    {
        abort_unless($property && $property->landlord_id === Auth::guard('landlord')->id(), 403);
    }
}
