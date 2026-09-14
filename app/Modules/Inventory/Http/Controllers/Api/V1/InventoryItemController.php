<?php

namespace App\Modules\Inventory\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Http\Requests\RecordInventoryTransactionRequest;
use App\Modules\Inventory\Http\Requests\StoreInventoryItemRequest;
use App\Modules\Inventory\Http\Requests\UpdateInventoryItemRequest;
use App\Modules\Inventory\Http\Resources\InventoryItemResource;
use App\Modules\Inventory\Models\InventoryItem;
use App\Modules\Inventory\Services\InventoryItemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InventoryItemController extends Controller
{
    public function __construct(protected InventoryItemService $service)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', InventoryItem::class);

        return InventoryItemResource::collection(
            $this->service->paginate(
                $request->string('search')->toString() ?: null,
                $request->string('category')->toString() ?: null,
                $request->string('status')->toString() ?: null,
                $request->boolean('low_stock'),
            )
        );
    }

    public function store(StoreInventoryItemRequest $request): JsonResponse
    {
        $item = $this->service->create([
            ...$request->validated(),
            'company_id' => $request->user()->company_id,
        ]);

        return InventoryItemResource::make($item->load(['property', 'vendor']))->response()->setStatusCode(201);
    }

    public function show(InventoryItem $inventoryItem): InventoryItemResource
    {
        $this->authorize('view', $inventoryItem);

        return InventoryItemResource::make($inventoryItem->load(['property', 'vendor', 'transactions'])->loadCount('transactions'));
    }

    public function update(UpdateInventoryItemRequest $request, InventoryItem $inventoryItem): InventoryItemResource
    {
        $inventoryItem = $this->service->update($inventoryItem, $request->validated());

        return InventoryItemResource::make($inventoryItem->load(['property', 'vendor']));
    }

    public function destroy(InventoryItem $inventoryItem): JsonResponse
    {
        $this->authorize('delete', $inventoryItem);

        $this->service->delete($inventoryItem);

        return response()->json(null, 204);
    }

    public function recordTransaction(RecordInventoryTransactionRequest $request, InventoryItem $inventoryItem): InventoryItemResource
    {
        $inventoryItem = $this->service->recordTransaction($inventoryItem, [
            ...$request->validated(),
            'recorded_by_id' => $request->user()->id,
        ]);

        return InventoryItemResource::make($inventoryItem->load(['property', 'vendor', 'transactions']));
    }
}
