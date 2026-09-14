<?php

namespace App\Modules\Vendors\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Vendors\Http\Requests\StoreVendorRequest;
use App\Modules\Vendors\Http\Requests\UpdateVendorRequest;
use App\Modules\Vendors\Models\Vendor;
use App\Modules\Vendors\Services\VendorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VendorController extends Controller
{
    public function __construct(protected VendorService $service)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Vendor::class);

        $vendors = $this->service->paginate(
            $request->string('search')->toString() ?: null,
            $request->string('category')->toString() ?: null,
            $request->string('status')->toString() ?: null,
        );

        return view('vendors::index', compact('vendors'));
    }

    public function create(): View
    {
        $this->authorize('create', Vendor::class);

        return view('vendors::create');
    }

    public function store(StoreVendorRequest $request): RedirectResponse
    {
        $vendor = $this->service->create([...$request->validated(), 'company_id' => $request->user()->company_id]);

        return redirect()->route('vendors.show', $vendor)->with('success', "Vendor \"{$vendor->name}\" created.");
    }

    public function show(Vendor $vendor): View
    {
        $this->authorize('view', $vendor);

        return view('vendors::show', [
            'vendor' => $vendor,
            'expenses' => $vendor->expenses()->with('account')->latest('expense_date')->limit(20)->get(),
            'maintenanceRequests' => $vendor->maintenanceRequests()->with('property')->latest('reported_date')->limit(10)->get(),
            'inventoryItems' => $vendor->inventoryItems()->with('property')->orderBy('name')->limit(10)->get(),
            'documents' => $vendor->documents()->latest()->limit(10)->get(),
        ]);
    }

    public function edit(Vendor $vendor): View
    {
        $this->authorize('update', $vendor);

        return view('vendors::edit', compact('vendor'));
    }

    public function update(UpdateVendorRequest $request, Vendor $vendor): RedirectResponse
    {
        $this->service->update($vendor, $request->validated());

        return redirect()->route('vendors.show', $vendor)->with('success', 'Vendor updated.');
    }

    public function destroy(Vendor $vendor): RedirectResponse
    {
        $this->authorize('delete', $vendor);

        $this->service->delete($vendor);

        return redirect()->route('vendors.index')->with('success', "Vendor \"{$vendor->name}\" deleted.");
    }
}
