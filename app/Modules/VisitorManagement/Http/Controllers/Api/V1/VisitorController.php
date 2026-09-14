<?php

namespace App\Modules\VisitorManagement\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\VisitorManagement\Http\Requests\CheckOutVisitorRequest;
use App\Modules\VisitorManagement\Http\Requests\StoreVisitorRequest;
use App\Modules\VisitorManagement\Http\Requests\UpdateVisitorRequest;
use App\Modules\VisitorManagement\Http\Resources\VisitorResource;
use App\Modules\VisitorManagement\Models\Visitor;
use App\Modules\VisitorManagement\Services\VisitorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class VisitorController extends Controller
{
    public function __construct(protected VisitorService $service)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Visitor::class);

        return VisitorResource::collection(
            $this->service->paginate(
                $request->string('search')->toString() ?: null,
                $request->integer('property_id') ?: null,
                $request->string('status')->toString() ?: null,
                $request->string('purpose')->toString() ?: null,
            )
        );
    }

    public function store(StoreVisitorRequest $request): JsonResponse
    {
        $visitor = $this->service->create([
            ...$request->validated(),
            'company_id' => $request->user()->company_id,
            'checked_in_by_id' => $request->user()->id,
        ]);

        return VisitorResource::make($visitor->load(['property', 'unit', 'tenant', 'vendor']))->response()->setStatusCode(201);
    }

    public function show(Visitor $visitor): VisitorResource
    {
        $this->authorize('view', $visitor);

        return VisitorResource::make($visitor->load(['property', 'unit', 'tenant', 'vendor', 'checkedInBy', 'checkedOutBy']));
    }

    public function update(UpdateVisitorRequest $request, Visitor $visitor): VisitorResource
    {
        $visitor = $this->service->update($visitor, $request->validated());

        return VisitorResource::make($visitor->load(['property', 'unit', 'tenant', 'vendor']));
    }

    public function destroy(Visitor $visitor): JsonResponse
    {
        $this->authorize('delete', $visitor);

        $this->service->delete($visitor);

        return response()->json(null, 204);
    }

    public function checkOut(CheckOutVisitorRequest $request, Visitor $visitor): VisitorResource
    {
        $visitor = $this->service->checkOut($visitor, [
            ...$request->validated(),
            'checked_out_by_id' => $request->user()->id,
        ]);

        return VisitorResource::make($visitor->load(['property', 'unit', 'tenant', 'vendor']));
    }
}
