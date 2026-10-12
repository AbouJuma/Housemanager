<?php

namespace App\Modules\LandlordPortal\Http\Controllers\Api\V1;

use App\Core\Http\Concerns\HasListFilters;
use App\Http\Controllers\Controller;
use App\Modules\LandlordPortal\Http\Requests\LandlordPropertyRequest;
use App\Modules\LandlordPortal\Services\LandlordManagementService;
use App\Modules\Properties\Http\Resources\PropertyResource;
use App\Modules\Properties\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * The landlord's own properties in the app - list (15/page, optional
 * ?status=), detail with every unit and who rents it, and add/edit
 * when their Landlord role allows it.
 */
class PropertyController extends Controller
{
    use HasListFilters;

    public function __construct(protected LandlordManagementService $service) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = $request->user()->properties()->withCount([
            'buildings',
            'units',
            'units as occupied_units_count' => fn ($q) => $q->where('units.status', 'occupied'),
            'units as vacant_units_count' => fn ($q) => $q->where('units.status', 'vacant'),
        ])->latest();
        $query = $this->applyStatusFilter($query, $request);

        return PropertyResource::collection($query->paginate(15)->withQueryString());
    }

    public function show(Request $request, Property $property): PropertyResource
    {
        abort_unless($property->landlord_id === $request->user()->id, 403);

        return PropertyResource::make($property->load('buildings.units.leases.tenant', 'buildings.units.sales.buyer'));
    }

    public function store(LandlordPropertyRequest $request): JsonResponse
    {
        $property = $this->service->createProperty($request->user(), $request->validated());

        return PropertyResource::make($property->loadCount(['buildings', 'units']))->response()->setStatusCode(201);
    }

    public function update(LandlordPropertyRequest $request, Property $property): PropertyResource
    {
        $property = $this->service->updateProperty($property, $request->validated());

        return PropertyResource::make($property->loadCount(['buildings', 'units']));
    }

    /** Choices for the app's property form. */
    public function options(): JsonResponse
    {
        return response()->json([
            'types' => LandlordPropertyRequest::TYPES,
            'statuses' => LandlordPropertyRequest::STATUSES,
            'regions' => config('tanzania.regions'),
            'areas' => config('tanzania.areas', []),
        ]);
    }
}
