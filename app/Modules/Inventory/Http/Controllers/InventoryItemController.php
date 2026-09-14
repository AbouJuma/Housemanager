<?php

namespace App\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Http\Requests\RecordInventoryTransactionRequest;
use App\Modules\Inventory\Http\Requests\StoreInventoryItemRequest;
use App\Modules\Inventory\Http\Requests\UpdateInventoryItemRequest;
use App\Modules\Inventory\Models\InventoryItem;
use App\Modules\Inventory\Services\InventoryItemService;
use App\Modules\Maintenance\Models\MaintenanceRequest;
use App\Modules\Properties\Models\Property;
use App\Modules\Vendors\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryItemController extends Controller
{
    public function __construct(protected InventoryItemService $service)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', InventoryItem::class);

        $items = $this->service->paginate(
            $request->string('search')->toString() ?: null,
            $request->string('category')->toString() ?: null,
            $request->string('status')->toString() ?: null,
            $request->boolean('low_stock'),
        );

        return view('inventory::index', ['items' => $items]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', InventoryItem::class);

        return view('inventory::create', [
            'properties' => $this->propertyOptions(),
            'vendors' => $this->vendorOptions(),
            'selectedPropertyUuid' => $request->query('property_id'),
        ]);
    }

    public function store(StoreInventoryItemRequest $request): RedirectResponse
    {
        $item = $this->service->create([
            ...$request->validated(),
            'company_id' => $request->user()->company_id,
        ]);

        return redirect()->route('inventory-items.show', $item)->with('success', 'Inventory item added.');
    }

    public function show(InventoryItem $inventoryItem): View
    {
        $this->authorize('view', $inventoryItem);

        return view('inventory::show', [
            'item' => $inventoryItem->load(['property', 'vendor', 'transactions.maintenanceRequest', 'transactions.recordedBy']),
            'maintenanceRequests' => $this->maintenanceRequestOptions(),
        ]);
    }

    public function edit(InventoryItem $inventoryItem): View
    {
        $this->authorize('update', $inventoryItem);

        return view('inventory::edit', [
            'item' => $inventoryItem,
            'properties' => $this->propertyOptions(),
            'vendors' => $this->vendorOptions(),
        ]);
    }

    public function update(UpdateInventoryItemRequest $request, InventoryItem $inventoryItem): RedirectResponse
    {
        $this->service->update($inventoryItem, $request->validated());

        return redirect()->route('inventory-items.show', $inventoryItem)->with('success', 'Inventory item updated.');
    }

    public function destroy(InventoryItem $inventoryItem): RedirectResponse
    {
        $this->authorize('delete', $inventoryItem);

        $this->service->delete($inventoryItem);

        return redirect()->route('inventory-items.index')->with('success', 'Inventory item deleted.');
    }

    public function recordTransaction(RecordInventoryTransactionRequest $request, InventoryItem $inventoryItem): RedirectResponse
    {
        $this->service->recordTransaction($inventoryItem, [
            ...$request->validated(),
            'recorded_by_id' => $request->user()->id,
        ]);

        return redirect()->route('inventory-items.show', $inventoryItem)->with('success', 'Transaction recorded.');
    }

    protected function propertyOptions(): \Illuminate\Support\Collection
    {
        return Property::query()->orderBy('name')->get(['id', 'uuid', 'name']);
    }

    protected function vendorOptions(): \Illuminate\Support\Collection
    {
        return Vendor::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']);
    }

    protected function maintenanceRequestOptions(): \Illuminate\Support\Collection
    {
        return MaintenanceRequest::query()
            ->whereIn('status', ['open', 'in_progress'])
            ->orderByDesc('reported_date')
            ->limit(50)
            ->get(['id', 'title']);
    }
}
