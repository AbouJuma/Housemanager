<?php

namespace App\Modules\Tenants\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Tenants\Http\Requests\StoreTenantRequest;
use App\Modules\Tenants\Http\Requests\UpdateTenantRequest;
use App\Modules\Tenants\Http\Resources\TenantResource;
use App\Modules\Tenants\Models\Tenant;
use App\Modules\Tenants\Services\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TenantController extends Controller
{
    public function __construct(protected TenantService $service)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Tenant::class);

        return TenantResource::collection(
            $this->service->paginate(
                $request->string('search')->toString() ?: null,
                $request->string('type')->toString() ?: null,
                $request->string('status')->toString() ?: null,
            )
        );
    }

    public function store(StoreTenantRequest $request): JsonResponse
    {
        $tenant = $this->service->create($request->validated());

        return TenantResource::make($tenant)->response()->setStatusCode(201);
    }

    public function show(Tenant $tenant): TenantResource
    {
        $this->authorize('view', $tenant);

        return TenantResource::make($tenant);
    }

    public function update(UpdateTenantRequest $request, Tenant $tenant): TenantResource
    {
        $tenant = $this->service->update($tenant, $request->validated());

        return TenantResource::make($tenant);
    }

    public function destroy(Tenant $tenant): JsonResponse
    {
        $this->authorize('delete', $tenant);

        $this->service->delete($tenant);

        return response()->json(null, 204);
    }
}
