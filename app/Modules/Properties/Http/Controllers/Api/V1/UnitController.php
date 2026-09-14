<?php

namespace App\Modules\Properties\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Properties\Http\Requests\StoreUnitRequest;
use App\Modules\Properties\Http\Requests\UpdateUnitRequest;
use App\Modules\Properties\Http\Resources\UnitResource;
use App\Modules\Properties\Models\Building;
use App\Modules\Properties\Models\Unit;
use App\Modules\Properties\Services\UnitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UnitController extends Controller
{
    public function __construct(protected UnitService $service)
    {
    }

    public function index(Building $building): AnonymousResourceCollection
    {
        $this->authorize('view', $building);

        return UnitResource::collection($this->service->forBuilding($building));
    }

    public function store(StoreUnitRequest $request, Building $building): JsonResponse
    {
        $unit = $this->service->create([...$request->validated(), 'building_id' => $building->id]);

        return UnitResource::make($unit)->response()->setStatusCode(201);
    }

    public function show(Unit $unit): UnitResource
    {
        $this->authorize('view', $unit);

        return UnitResource::make($unit);
    }

    public function update(UpdateUnitRequest $request, Unit $unit): UnitResource
    {
        $unit = $this->service->update($unit, $request->validated());

        return UnitResource::make($unit);
    }

    public function destroy(Unit $unit): JsonResponse
    {
        $this->authorize('delete', $unit);

        $this->service->delete($unit);

        return response()->json(null, 204);
    }
}
