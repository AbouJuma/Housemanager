<?php

namespace App\Modules\LandlordPortal\Http\Controllers\Api\V1;

use App\Core\Http\Concerns\HasListFilters;
use App\Http\Controllers\Controller;
use App\Modules\Properties\Http\Resources\PropertyResource;
use App\Modules\Properties\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * JSON mirror of App\Modules\LandlordPortal\Http\Controllers\
 * LandlordPortalPropertyController - paginated (15/page) with optional
 * ?status=.
 */
class PropertyController extends Controller
{
    use HasListFilters;

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = $request->user()->properties()->withCount('buildings');
        $query = $this->applyStatusFilter($query, $request);

        return PropertyResource::collection($query->paginate(15)->withQueryString());
    }

    public function show(Request $request, Property $property): PropertyResource
    {
        abort_unless($property->landlord_id === $request->user()->id, 403);

        return PropertyResource::make($property->load('buildings.units.leases.tenant', 'buildings.units.sales.buyer'));
    }
}
