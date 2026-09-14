<?php

namespace App\Modules\Vendors\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Vendors\Http\Requests\StoreVendorRequest;
use App\Modules\Vendors\Http\Requests\UpdateVendorRequest;
use App\Modules\Vendors\Http\Resources\VendorResource;
use App\Modules\Vendors\Models\Vendor;
use App\Modules\Vendors\Services\VendorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class VendorController extends Controller
{
    public function __construct(protected VendorService $service)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Vendor::class);

        return VendorResource::collection(
            $this->service->paginate(
                $request->string('search')->toString() ?: null,
                $request->string('category')->toString() ?: null,
                $request->string('status')->toString() ?: null,
            )
        );
    }

    public function store(StoreVendorRequest $request): JsonResponse
    {
        $vendor = $this->service->create([...$request->validated(), 'company_id' => $request->user()->company_id]);

        return VendorResource::make($vendor)->response()->setStatusCode(201);
    }

    public function show(Vendor $vendor): VendorResource
    {
        $this->authorize('view', $vendor);

        return VendorResource::make($vendor->loadCount('expenses'));
    }

    public function update(UpdateVendorRequest $request, Vendor $vendor): VendorResource
    {
        $vendor = $this->service->update($vendor, $request->validated());

        return VendorResource::make($vendor);
    }

    public function destroy(Vendor $vendor): JsonResponse
    {
        $this->authorize('delete', $vendor);

        $this->service->delete($vendor);

        return response()->json(null, 204);
    }
}
