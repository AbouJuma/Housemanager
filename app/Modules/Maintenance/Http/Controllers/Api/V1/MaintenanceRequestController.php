<?php

namespace App\Modules\Maintenance\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Maintenance\Http\Requests\RecordMaintenanceExpenseRequest;
use App\Modules\Maintenance\Http\Requests\StoreMaintenanceRequestRequest;
use App\Modules\Maintenance\Http\Requests\UpdateMaintenanceRequestRequest;
use App\Modules\Maintenance\Http\Resources\MaintenanceRequestResource;
use App\Modules\Maintenance\Models\MaintenanceRequest;
use App\Modules\Maintenance\Services\MaintenanceRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MaintenanceRequestController extends Controller
{
    public function __construct(protected MaintenanceRequestService $service)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', MaintenanceRequest::class);

        return MaintenanceRequestResource::collection(
            $this->service->paginate(
                $request->string('search')->toString() ?: null,
                $request->integer('property_id') ?: null,
                $request->string('status')->toString() ?: null,
                $request->string('priority')->toString() ?: null,
            )
        );
    }

    public function store(StoreMaintenanceRequestRequest $request): JsonResponse
    {
        $maintenanceRequest = $this->service->create([
            ...$request->validated(),
            'company_id' => $request->user()->company_id,
            'reported_by_id' => $request->user()->id,
        ]);

        return MaintenanceRequestResource::make($maintenanceRequest->load(['property', 'unit', 'vendor']))->response()->setStatusCode(201);
    }

    public function show(MaintenanceRequest $maintenanceRequest): MaintenanceRequestResource
    {
        $this->authorize('view', $maintenanceRequest);

        return MaintenanceRequestResource::make($maintenanceRequest->load(['property', 'unit', 'tenant', 'vendor', 'expense']));
    }

    public function update(UpdateMaintenanceRequestRequest $request, MaintenanceRequest $maintenanceRequest): MaintenanceRequestResource
    {
        $maintenanceRequest = $this->service->update($maintenanceRequest, $request->validated());

        return MaintenanceRequestResource::make($maintenanceRequest->load(['property', 'unit', 'vendor']));
    }

    /**
     * Just the status (and an optional note) - what a caretaker, cleaner or
     * vendor updates from the mobile app, without the full edit form.
     */
    public function updateStatus(Request $request, MaintenanceRequest $maintenanceRequest): MaintenanceRequestResource
    {
        $this->authorize('update', $maintenanceRequest);

        $data = $request->validate([
            'status' => ['required', 'in:open,in_progress,completed,cancelled'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $changes = ['status' => $data['status']];
        if ($data['status'] === 'completed' && ! $maintenanceRequest->completed_date) {
            $changes['completed_date'] = company_now()->toDateString();
        }
        if (filled($data['notes'] ?? null)) {
            $changes['notes'] = trim($maintenanceRequest->notes."
".$data['notes']);
        }

        $maintenanceRequest = $this->service->update($maintenanceRequest, $changes);

        return MaintenanceRequestResource::make($maintenanceRequest->load(['property', 'unit', 'tenant', 'vendor']));
    }

    public function destroy(MaintenanceRequest $maintenanceRequest): JsonResponse
    {
        $this->authorize('delete', $maintenanceRequest);

        $this->service->delete($maintenanceRequest);

        return response()->json(null, 204);
    }

    public function recordExpense(RecordMaintenanceExpenseRequest $request, MaintenanceRequest $maintenanceRequest): MaintenanceRequestResource
    {
        $maintenanceRequest = $this->service->recordExpense($maintenanceRequest, [
            ...$request->validated(),
            'recorded_by_id' => $request->user()->id,
        ]);

        return MaintenanceRequestResource::make($maintenanceRequest->load(['property', 'unit', 'vendor', 'expense']));
    }
}
