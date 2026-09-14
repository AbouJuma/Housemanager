<?php

namespace App\Modules\Properties\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Properties\Http\Requests\StoreBuildingRequest;
use App\Modules\Properties\Http\Requests\UpdateBuildingRequest;
use App\Modules\Properties\Models\Building;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Services\BuildingService;
use App\Modules\Properties\Services\UnitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BuildingController extends Controller
{
    public function __construct(protected BuildingService $service)
    {
    }

    public function create(Property $property): View
    {
        $this->authorize('create', [Building::class, $property]);

        return view('properties::buildings.create', compact('property'));
    }

    public function store(StoreBuildingRequest $request, Property $property): RedirectResponse
    {
        $building = $this->service->create([...$request->validated(), 'property_id' => $property->id]);

        return redirect()->route('buildings.show', $building)->with('success', "Building \"{$building->name}\" created.");
    }

    public function show(Building $building, UnitService $units): View
    {
        $this->authorize('view', $building);

        return view('properties::buildings.show', [
            'building' => $building,
            'units' => $units->forBuilding($building),
        ]);
    }

    public function edit(Building $building): View
    {
        $this->authorize('update', $building);

        return view('properties::buildings.edit', compact('building'));
    }

    public function update(UpdateBuildingRequest $request, Building $building): RedirectResponse
    {
        $this->service->update($building, $request->validated());

        return redirect()->route('buildings.show', $building)->with('success', 'Building updated.');
    }

    public function destroy(Building $building): RedirectResponse
    {
        $this->authorize('delete', $building);

        $property = $building->property;
        $this->service->delete($building);

        return redirect()->route('properties.show', $property)->with('success', "Building \"{$building->name}\" deleted.");
    }
}
