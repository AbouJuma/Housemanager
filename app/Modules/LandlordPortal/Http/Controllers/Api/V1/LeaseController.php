<?php

namespace App\Modules\LandlordPortal\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\LandlordPortal\Http\Requests\LandlordLeaseRequest;
use App\Modules\Leases\Http\Resources\LeaseResource;
use App\Modules\Leases\Services\LeaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Landlords renting out their units from the app. */
class LeaseController extends Controller
{
    public function __construct(protected LeaseService $leases) {}

    /** Their free units (with price and what it's per) and their tenants. */
    public function options(Request $request): JsonResponse
    {
        $landlord = $request->user();

        return response()->json([
            'units' => $landlord->unitsQuery()->where('status', 'vacant')->with('building.property')->orderBy('unit_number')->get()
                ->map(fn ($unit) => [
                    'uuid' => $unit->uuid,
                    'unit_number' => $unit->unit_number,
                    'building' => $unit->building?->name,
                    'property' => $unit->building?->property?->name,
                    'rent_amount' => (float) $unit->rent_amount,
                    'rent_period' => $unit->rent_period ?? 'monthly',
                ])->values(),
            'tenants' => $landlord->tenantsQuery()->where('status', '!=', 'blacklisted')->orderBy('name')->get(['uuid', 'name', 'phone']),
            'billing_cycles' => ['monthly', 'quarterly', 'semi_annual', 'annual'],
        ]);
    }

    public function store(LandlordLeaseRequest $request): JsonResponse
    {
        $lease = $this->leases->create($request->validated());

        return LeaseResource::make($lease->load(['tenant', 'unit.building.property']))->response()->setStatusCode(201);
    }
}
