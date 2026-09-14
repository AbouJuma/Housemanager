<?php

namespace App\Modules\Maintenance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Accounting\Models\Account;
use App\Modules\Maintenance\Http\Requests\RecordMaintenanceExpenseRequest;
use App\Modules\Maintenance\Http\Requests\StoreMaintenanceRequestRequest;
use App\Modules\Maintenance\Http\Requests\UpdateMaintenanceRequestRequest;
use App\Modules\Maintenance\Models\MaintenanceRequest;
use App\Modules\Maintenance\Services\MaintenanceRequestService;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use App\Modules\Tenants\Models\Tenant;
use App\Modules\Vendors\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MaintenanceRequestController extends Controller
{
    public function __construct(protected MaintenanceRequestService $service)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', MaintenanceRequest::class);

        $requests = $this->service->paginate(
            $request->string('search')->toString() ?: null,
            $request->integer('property_id') ?: null,
            $request->string('status')->toString() ?: null,
            $request->string('priority')->toString() ?: null,
        );

        return view('maintenance::index', [
            'requests' => $requests,
            'properties' => $this->propertyOptions(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', MaintenanceRequest::class);

        return view('maintenance::create', [
            'properties' => $this->propertyOptions(),
            'units' => $this->unitOptions(),
            'tenants' => $this->tenantOptions(),
            'vendors' => $this->vendorOptions(),
            'selectedPropertyUuid' => $request->query('property_id'),
        ]);
    }

    public function store(StoreMaintenanceRequestRequest $request): RedirectResponse
    {
        $maintenanceRequest = $this->service->create([
            ...$request->validated(),
            'company_id' => $request->user()->company_id,
            'reported_by_id' => $request->user()->id,
        ]);

        return redirect()->route('maintenance-requests.show', $maintenanceRequest)->with('success', 'Maintenance request logged.');
    }

    public function show(MaintenanceRequest $maintenanceRequest): View
    {
        $this->authorize('view', $maintenanceRequest);

        return view('maintenance::show', [
            'maintenanceRequest' => $maintenanceRequest->load(['property', 'unit', 'tenant', 'vendor', 'expense', 'reportedBy']),
            'expenseAccounts' => $this->expenseAccountOptions(),
            'inventoryTransactions' => $maintenanceRequest->inventoryTransactions()->with('item')->get(),
        ]);
    }

    public function edit(MaintenanceRequest $maintenanceRequest): View
    {
        $this->authorize('update', $maintenanceRequest);

        return view('maintenance::edit', [
            'maintenanceRequest' => $maintenanceRequest,
            'properties' => $this->propertyOptions(),
            'units' => $this->unitOptions(),
            'tenants' => $this->tenantOptions(),
            'vendors' => $this->vendorOptions(),
        ]);
    }

    public function update(UpdateMaintenanceRequestRequest $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        $this->service->update($maintenanceRequest, $request->validated());

        return redirect()->route('maintenance-requests.show', $maintenanceRequest)->with('success', 'Maintenance request updated.');
    }

    public function destroy(MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        $this->authorize('delete', $maintenanceRequest);

        $this->service->delete($maintenanceRequest);

        return redirect()->route('maintenance-requests.index')->with('success', 'Maintenance request deleted.');
    }

    public function recordExpense(RecordMaintenanceExpenseRequest $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        $this->service->recordExpense($maintenanceRequest, [
            ...$request->validated(),
            'recorded_by_id' => $request->user()->id,
        ]);

        return redirect()->route('maintenance-requests.show', $maintenanceRequest)->with('success', 'Expense recorded against this request.');
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
        return Vendor::query()->where('status', 'active')->orderBy('name')->get(['id', 'name', 'category']);
    }

    protected function expenseAccountOptions(): \Illuminate\Support\Collection
    {
        return Account::query()->where('type', 'expense')->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']);
    }
}
