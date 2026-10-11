<?php

namespace App\Modules\Leases\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Leases\Http\Requests\StoreLeaseRequest;
use App\Modules\Leases\Http\Requests\UpdateLeaseRequest;
use App\Modules\Leases\Models\Lease;
use App\Modules\Leases\Services\LeaseService;
use App\Modules\Properties\Models\Unit;
use App\Modules\Tenants\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaseController extends Controller
{
    public function __construct(protected LeaseService $service)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Lease::class);

        $leases = $this->service->paginate(
            $request->string('search')->toString() ?: null,
            $request->string('status')->toString() ?: null,
        );

        return view('leases::index', compact('leases'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Lease::class);

        return view('leases::create', [
            'tenants' => $this->tenantOptions(),
            'units' => $this->unitOptions(),
            'selectedUnitUuid' => $request->query('unit_id'),
        ]);
    }

    public function store(StoreLeaseRequest $request): RedirectResponse
    {
        $lease = $this->service->create($request->validated());

        return redirect()->route('leases.show', $lease)->with('success', 'Lease created.');
    }

    public function show(Lease $lease): View
    {
        $this->authorize('view', $lease);

        return view('leases::show', [
            'lease' => $lease->load(['tenant', 'unit.building.property']),
            'invoices' => $lease->invoices()->latest()->get(),
            'documents' => $lease->documents()->latest()->limit(10)->get(),
        ]);
    }

    public function edit(Lease $lease): View
    {
        $this->authorize('update', $lease);

        return view('leases::edit', [
            'lease' => $lease->load(['tenant', 'unit.building.property']),
        ]);
    }

    public function update(UpdateLeaseRequest $request, Lease $lease): RedirectResponse
    {
        $this->service->update($lease, $request->validated());

        return redirect()->route('leases.show', $lease)->with('success', 'Lease updated.');
    }

    public function destroy(Lease $lease): RedirectResponse
    {
        $this->authorize('delete', $lease);

        $this->service->delete($lease);

        return redirect()->route('leases.index')->with('success', 'Lease deleted.');
    }

    /**
     * Excludes blacklisted tenants - the same server-side rule
     * StoreLeaseRequest enforces, mirrored here so the dropdown never
     * even offers an option the backend would reject.
     */
    protected function tenantOptions(): \Illuminate\Support\Collection
    {
        return Tenant::query()->where('status', '!=', 'blacklisted')->orderBy('name')->get(['id', 'uuid', 'name']);
    }

    protected function unitOptions(): \Illuminate\Support\Collection
    {
        return Unit::query()
            ->where('status', 'vacant')
            ->with('building.property')
            ->orderBy('unit_number')
            // rent_amount feeds the form's data-rent-amount, which
            // prefills Rent Amount when a unit is picked.
            ->get(['id', 'uuid', 'unit_number', 'building_id', 'rent_amount']);
    }
}
