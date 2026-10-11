<?php

namespace App\Modules\Properties\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Landlords\Models\Landlord;
use App\Modules\Properties\Http\Requests\StorePropertyRequest;
use App\Modules\Properties\Http\Requests\UpdatePropertyRequest;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Services\BuildingService;
use App\Modules\Properties\Services\PropertyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PropertyController extends Controller
{
    public function __construct(protected PropertyService $service)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Property::class);

        $properties = $this->service->paginate(
            $request->string('search')->toString() ?: null,
            $request->string('type')->toString() ?: null,
            $request->string('status')->toString() ?: null,
            region: $request->string('region')->toString() ?: null,
            district: $request->string('district')->toString() ?: null,
        );

        return view('properties::properties.index', [
            'properties' => $properties,
            'regions' => config('tanzania.regions'),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Property::class);

        return view('properties::properties.create', [
            'landlords' => $this->landlordOptions(),
        ]);
    }

    public function store(StorePropertyRequest $request): RedirectResponse
    {
        $property = $this->service->create($request->validated());

        return redirect()->route('properties.show', $property)->with('success', "Property \"{$property->name}\" created.");
    }

    public function show(Property $property, BuildingService $buildings): View
    {
        $this->authorize('view', $property);

        return view('properties::properties.show', [
            'property' => $property,
            'buildings' => $buildings->forProperty($property),
            'maintenanceRequests' => $property->maintenanceRequests()->latest('reported_date')->limit(10)->get(),
            'inventoryItems' => $property->inventoryItems()->orderBy('name')->limit(10)->get(),
            'documents' => $property->documents()->latest()->limit(10)->get(),
            'visitors' => $property->visitors()->latest('check_in_at')->limit(10)->get(),
        ]);
    }

    public function edit(Property $property): View
    {
        $this->authorize('update', $property);

        return view('properties::properties.edit', [
            'property' => $property,
            'landlords' => $this->landlordOptions(),
        ]);
    }

    public function update(UpdatePropertyRequest $request, Property $property): RedirectResponse
    {
        $this->service->update($property, $request->validated());

        return redirect()->route('properties.show', $property)->with('success', 'Property updated.');
    }

    public function destroy(Property $property): RedirectResponse
    {
        $this->authorize('delete', $property);

        $this->service->delete($property);

        return redirect()->route('properties.index')->with('success', "Property \"{$property->name}\" deleted.");
    }

    /**
     * @return \Illuminate\Support\Collection<int, Landlord>
     */
    protected function landlordOptions(): \Illuminate\Support\Collection
    {
        // BelongsToCompany's global scope already limits this to the
        // acting user's own company - no explicit company_id filter
        // needed here.
        return Landlord::query()->orderBy('name')->get(['id', 'uuid', 'name']);
    }
}
