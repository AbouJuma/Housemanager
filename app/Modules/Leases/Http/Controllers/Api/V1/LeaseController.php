<?php

namespace App\Modules\Leases\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Leases\Http\Requests\StoreLeaseRequest;
use App\Modules\Leases\Http\Requests\UpdateLeaseRequest;
use App\Modules\Leases\Http\Resources\LeaseResource;
use App\Modules\Leases\Models\Lease;
use App\Modules\Leases\Services\LeaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LeaseController extends Controller
{
    public function __construct(protected LeaseService $service)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Lease::class);

        return LeaseResource::collection(
            $this->service->paginate(
                $request->string('search')->toString() ?: null,
                $request->string('status')->toString() ?: null,
                from: $request->string('from')->toString() ?: null,
                to: $request->string('to')->toString() ?: null,
            )
        );
    }

    public function store(StoreLeaseRequest $request): JsonResponse
    {
        $lease = $this->service->create($request->validated());

        return LeaseResource::make($lease->load(['tenant', 'unit']))->response()->setStatusCode(201);
    }

    public function show(Lease $lease): LeaseResource
    {
        $this->authorize('view', $lease);

        return LeaseResource::make($lease->load(['tenant', 'unit.building.property']));
    }

    public function update(UpdateLeaseRequest $request, Lease $lease): LeaseResource
    {
        $lease = $this->service->update($lease, $request->validated());

        return LeaseResource::make($lease->load(['tenant', 'unit']));
    }

    public function destroy(Lease $lease): JsonResponse
    {
        $this->authorize('delete', $lease);

        $this->service->delete($lease);

        return response()->json(null, 204);
    }
}
