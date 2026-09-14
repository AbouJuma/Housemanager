<?php

namespace App\Modules\VisitorManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use App\Modules\Tenants\Models\Tenant;
use App\Modules\Vendors\Models\Vendor;
use App\Modules\VisitorManagement\Http\Requests\CheckOutVisitorRequest;
use App\Modules\VisitorManagement\Http\Requests\StoreVisitorRequest;
use App\Modules\VisitorManagement\Http\Requests\UpdateVisitorRequest;
use App\Modules\VisitorManagement\Models\Visitor;
use App\Modules\VisitorManagement\Services\VisitorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VisitorController extends Controller
{
    public function __construct(protected VisitorService $service)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Visitor::class);

        $visitors = $this->service->paginate(
            $request->string('search')->toString() ?: null,
            $request->integer('property_id') ?: null,
            $request->string('status')->toString() ?: null,
            $request->string('purpose')->toString() ?: null,
        );

        return view('visitor-management::index', [
            'visitors' => $visitors,
            'properties' => $this->propertyOptions(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Visitor::class);

        return view('visitor-management::create', [
            'properties' => $this->propertyOptions(),
            'units' => $this->unitOptions(),
            'tenants' => $this->tenantOptions(),
            'vendors' => $this->vendorOptions(),
            'selectedPropertyUuid' => $request->query('property_id'),
        ]);
    }

    public function store(StoreVisitorRequest $request): RedirectResponse
    {
        $visitor = $this->service->create([
            ...$request->validated(),
            'company_id' => $request->user()->company_id,
            'checked_in_by_id' => $request->user()->id,
        ]);

        return redirect()->route('visitors.show', $visitor)->with('success', 'Visitor checked in.');
    }

    public function show(Visitor $visitor): View
    {
        $this->authorize('view', $visitor);

        return view('visitor-management::show', [
            'visitor' => $visitor->load(['property', 'unit', 'tenant', 'vendor', 'checkedInBy', 'checkedOutBy']),
        ]);
    }

    public function edit(Visitor $visitor): View
    {
        $this->authorize('update', $visitor);

        return view('visitor-management::edit', [
            'visitor' => $visitor,
            'properties' => $this->propertyOptions(),
            'units' => $this->unitOptions(),
            'tenants' => $this->tenantOptions(),
            'vendors' => $this->vendorOptions(),
        ]);
    }

    public function update(UpdateVisitorRequest $request, Visitor $visitor): RedirectResponse
    {
        $this->service->update($visitor, $request->validated());

        return redirect()->route('visitors.show', $visitor)->with('success', 'Visitor updated.');
    }

    public function destroy(Visitor $visitor): RedirectResponse
    {
        $this->authorize('delete', $visitor);

        $this->service->delete($visitor);

        return redirect()->route('visitors.index')->with('success', 'Visitor record deleted.');
    }

    public function checkOut(CheckOutVisitorRequest $request, Visitor $visitor): RedirectResponse
    {
        $this->service->checkOut($visitor, [
            ...$request->validated(),
            'checked_out_by_id' => $request->user()->id,
        ]);

        return redirect()->route('visitors.show', $visitor)->with('success', 'Visitor checked out.');
    }

    protected function propertyOptions(): \Illuminate\Support\Collection
    {
        return Property::query()->orderBy('name')->get(['id', 'uuid', 'name']);
    }

    protected function unitOptions(): \Illuminate\Support\Collection
    {
        return Unit::query()->with('building.property')->orderBy('unit_number')->get(['id', 'unit_number', 'building_id']);
    }

    protected function tenantOptions(): \Illuminate\Support\Collection
    {
        return Tenant::query()->where('status', '!=', 'blacklisted')->orderBy('name')->get(['id', 'name']);
    }

    protected function vendorOptions(): \Illuminate\Support\Collection
    {
        return Vendor::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']);
    }
}
