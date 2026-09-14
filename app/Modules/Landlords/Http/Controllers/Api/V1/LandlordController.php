<?php

namespace App\Modules\Landlords\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Landlords\Http\Requests\StoreLandlordRequest;
use App\Modules\Landlords\Http\Requests\UpdateLandlordRequest;
use App\Modules\Landlords\Http\Resources\LandlordResource;
use App\Modules\Landlords\Models\Landlord;
use App\Modules\Landlords\Services\LandlordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LandlordController extends Controller
{
    public function __construct(protected LandlordService $service)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Landlord::class);

        return LandlordResource::collection(
            $this->service->paginate(
                $request->string('search')->toString() ?: null,
                $request->string('type')->toString() ?: null,
                $request->string('status')->toString() ?: null,
            )
        );
    }

    public function store(StoreLandlordRequest $request): JsonResponse
    {
        $landlord = $this->service->create($request->validated());

        return LandlordResource::make($landlord)->response()->setStatusCode(201);
    }

    public function show(Landlord $landlord): LandlordResource
    {
        $this->authorize('view', $landlord);

        return LandlordResource::make($landlord->loadCount('properties'));
    }

    public function update(UpdateLandlordRequest $request, Landlord $landlord): LandlordResource
    {
        $landlord = $this->service->update($landlord, $request->validated());

        return LandlordResource::make($landlord);
    }

    public function destroy(Landlord $landlord): JsonResponse
    {
        $this->authorize('delete', $landlord);

        $this->service->delete($landlord);

        return response()->json(null, 204);
    }
}
