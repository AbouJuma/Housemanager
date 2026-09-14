<?php

namespace App\Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use App\Modules\Sales\Http\Requests\StoreSaleRequest;
use App\Modules\Sales\Http\Requests\UpdateSaleRequest;
use App\Modules\Sales\Models\Buyer;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Services\SaleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function __construct(protected SaleService $service)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Sale::class);

        $sales = $this->service->paginate(
            $request->string('search')->toString() ?: null,
            $request->string('status')->toString() ?: null,
            $request->integer('property_id') ?: null,
        );

        return view('sales::index', [
            'sales' => $sales,
            'properties' => Property::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Sale::class);

        $selectedUnitUuid = $request->query('unit_id');

        return view('sales::create', [
            'units' => $this->unitOptions(),
            'buyers' => $this->buyerOptions(),
            'selectedUnitUuid' => $selectedUnitUuid,
        ]);
    }

    public function store(StoreSaleRequest $request): RedirectResponse
    {
        $sale = $this->service->create([
            ...$request->validated(),
            'company_id' => $request->user()->company_id,
            'recorded_by_id' => $request->user()->id,
        ]);

        return redirect()->route('sales.show', $sale)->with('success', 'Sale recorded successfully.');
    }

    public function show(Sale $sale): View
    {
        $this->authorize('view', $sale);

        return view('sales::show', [
            'sale' => $sale->load(['unit.building.property', 'buyer', 'recordedBy', 'installments']),
        ]);
    }

    public function edit(Sale $sale): View
    {
        $this->authorize('update', $sale);

        return view('sales::edit', [
            'sale' => $sale,
            'units' => $this->unitOptions(),
            'buyers' => $this->buyerOptions(),
        ]);
    }

    public function update(UpdateSaleRequest $request, Sale $sale): RedirectResponse
    {
        $this->service->update($sale, $request->validated());

        return redirect()->route('sales.show', $sale)->with('success', 'Sale updated successfully.');
    }

    public function destroy(Sale $sale): RedirectResponse
    {
        $this->authorize('delete', $sale);

        $this->service->delete($sale);

        return redirect()->route('sales.index')->with('success', 'Sale deleted successfully.');
    }

    protected function unitOptions(): \Illuminate\Support\Collection
    {
        return Unit::query()
            ->whereHas('building.property')
            ->whereDoesntHave('sales', function ($query) {
                $query->whereIn('status', ['active', 'completed']);
            })
            ->with(['building.property'])
            ->orderBy('unit_number')
            ->get(['id', 'uuid', 'unit_number', 'building_id']);
    }

    protected function buyerOptions(): \Illuminate\Support\Collection
    {
        return Buyer::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'uuid', 'name']);
    }
}
