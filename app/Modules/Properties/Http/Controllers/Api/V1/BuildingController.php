<?php

namespace App\Modules\Properties\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Properties\Http\Requests\StoreBuildingRequest;
use App\Modules\Properties\Http\Requests\UpdateBuildingRequest;
use App\Modules\Properties\Http\Resources\BuildingResource;
use App\Modules\Properties\Models\Building;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Services\BuildingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BuildingController extends Controller
{
    public function __construct(protected BuildingService $service)
    {
    }

    public function index(Property $property): AnonymousResourceCollection
    {
        $this->authorize('view', $property);

        return BuildingResource::collection($this->service->forProperty($property));
    }

    public function store(StoreBuildingRequest $request, Property $property): JsonResponse
    {
        $building = $this->service->create([...$request->validated(), 'property_id' => $property->id]);

        return BuildingResource::make($building)->response()->setStatusCode(201);
    }

    public function show(Building $building): BuildingResource
    {
        $this->authorize('view', $building);

        return BuildingResource::make($building->loadCount('units'));
    }

    public function update(UpdateBuildingRequest $request, Building $building): BuildingResource
    {
        $building = $this->service->update($building, $request->validated());

        return BuildingResource::make($building);
    }

    public function destroy(Building $building): JsonResponse
    {
        $this->authorize('delete', $building);

        $this->service->delete($building);

        return response()->json(null, 204);
    }
}
