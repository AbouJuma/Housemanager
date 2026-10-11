<?php

namespace App\Modules\Properties\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Properties\Http\Requests\StoreUnitRequest;
use App\Modules\Properties\Http\Requests\UpdateUnitRequest;
use App\Modules\Properties\Models\Building;
use App\Modules\Properties\Models\Unit;
use App\Modules\Properties\Services\UnitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UnitController extends Controller
{
    public function __construct(protected UnitService $service)
    {
    }

    public function create(Building $building): View
    {
        $this->authorize('create', [Unit::class, $building]);

        return view('properties::units.create', compact('building'));
    }

    public function store(StoreUnitRequest $request, Building $building): RedirectResponse
    {
        $unit = $this->service->create([...$request->validated(), 'building_id' => $building->id]);

        return redirect()->route('units.show', $unit)->with('success', "Unit \"{$unit->unit_number}\" created.");
    }

    public function show(Unit $unit): View
    {
        $this->authorize('view', $unit);

        return view('properties::units.show', [
            'unit' => $unit,
            'currentLease' => $unit->currentLease(),
        ]);
    }

    public function edit(Unit $unit): View
    {
        $this->authorize('update', $unit);

        return view('properties::units.edit', compact('unit'));
    }

    public function update(UpdateUnitRequest $request, Unit $unit): RedirectResponse
    {
        $this->service->update($unit, $request->validated());

        return redirect()->route('units.show', $unit)->with('success', 'Unit updated.');
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        $this->authorize('delete', $unit);

        $building = $unit->building;
        $this->service->delete($unit);

        return redirect()->route('buildings.show', $building)->with('success', "Unit \"{$unit->unit_number}\" deleted.");
    }

    public function createMultiple(Building $building): View
    {
        $this->authorize('create', [Unit::class, $building]);

        return view('properties::units.create_multiple', compact('building'));
    }

    public function storeMultiple(Request $request, Building $building): JsonResponse
    {
        $this->authorize('create', [Unit::class, $building]);

        $units = [];
        $errors = [];

        foreach ($request->input('units', []) as $index => $unitData) {
            try {
                $unit = $this->service->create([
                    ...$unitData,
                    'building_id' => $building->id,
                ]);
                $units[] = [
                    'id' => $unit->id,
                    'uuid' => $unit->uuid,
                    'unit_number' => $unit->unit_number,
                ];
            } catch (\Exception $e) {
                $errors[] = [
                    'index' => $index,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => count($units) > 0,
            'units' => $units,
            'errors' => $errors,
            'total' => count($units),
            'failed' => count($errors),
        ]);
    }
}
