<?php

namespace App\Modules\Properties\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Properties\Http\Requests\StorePropertyRequest;
use App\Modules\Properties\Http\Requests\UpdatePropertyRequest;
use App\Modules\Properties\Http\Resources\PropertyResource;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Services\PropertyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PropertyController extends Controller
{
    public function __construct(protected PropertyService $service)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Property::class);

        return PropertyResource::collection(
            $this->service->paginate(
                $request->string('search')->toString() ?: null,
                $request->string('type')->toString() ?: null,
                $request->string('status')->toString() ?: null,
                region: $request->string('region')->toString() ?: null,
                district: $request->string('district')->toString() ?: null,
            )
        );
    }

    public function store(StorePropertyRequest $request): JsonResponse
    {
        $property = $this->service->create($request->validated());

        return PropertyResource::make($property)->response()->setStatusCode(201);
    }

    public function show(Property $property): PropertyResource
    {
        $this->authorize('view', $property);

        return PropertyResource::make($property->loadCount(['buildings', 'units'])->load('landlord'));
    }

    public function update(UpdatePropertyRequest $request, Property $property): PropertyResource
    {
        $property = $this->service->update($property, $request->validated());

        return PropertyResource::make($property);
    }

    public function destroy(Property $property): JsonResponse
    {
        $this->authorize('delete', $property);

        $this->service->delete($property);

        return response()->json(null, 204);
    }
}
