<?php

namespace App\Modules\Tenants\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tenants\Http\Requests\StoreTenantRequest;
use App\Modules\Tenants\Http\Requests\UpdateTenantRequest;
use App\Modules\Tenants\Models\Tenant;
use App\Modules\Tenants\Services\TenantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantController extends Controller
{
    public function __construct(protected TenantService $service)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Tenant::class);

        $tenants = $this->service->paginate(
            $request->string('search')->toString() ?: null,
            $request->string('type')->toString() ?: null,
            $request->string('status')->toString() ?: null,
        );

        return view('tenants::index', compact('tenants'));
    }

    public function create(): View
    {
        $this->authorize('create', Tenant::class);

        return view('tenants::create');
    }

    public function store(StoreTenantRequest $request): RedirectResponse
    {
        $tenant = $this->service->create($request->validated());

        return redirect()->route('tenants.show', $tenant)->with('success', __('Tenant ":name" created. Portal login - username: :username, password: :password', ['name' => $tenant->name, 'username' => $tenant->username, 'password' => $this->service->issuedPassword()]));
    }

    public function show(Tenant $tenant): View
    {
        $this->authorize('view', $tenant);

        return view('tenants::show', [
            'tenant' => $tenant,
            'leases' => $tenant->leases()->with('unit.building.property')->latest()->get(),
            'documents' => $tenant->documents()->latest()->limit(10)->get(),
            'visitors' => $tenant->visitors()->latest('check_in_at')->limit(10)->get(),
        ]);
    }

    public function edit(Tenant $tenant): View
    {
        $this->authorize('update', $tenant);

        return view('tenants::edit', compact('tenant'));
    }

    public function update(UpdateTenantRequest $request, Tenant $tenant): RedirectResponse
    {
        $this->service->update($tenant, $request->validated());

        return redirect()->route('tenants.show', $tenant)->with('success', 'Tenant updated.');
    }

    public function destroy(Tenant $tenant): RedirectResponse
    {
        $this->authorize('delete', $tenant);

        $this->service->delete($tenant);

        return redirect()->route('tenants.index')->with('success', "Tenant \"{$tenant->name}\" deleted.");
    }

    /**
     * AJAX endpoint for creating a tenant from another form
     */
    public function storeAjax(StoreTenantRequest $request): \Illuminate\Http\JsonResponse
    {
        $tenant = $this->service->create($request->validated());

        return response()->json([
            'success' => true,
            'tenant' => [
                'id' => $tenant->id,
                'uuid' => $tenant->uuid,
                'name' => $tenant->name,
            ],
        ]);
    }

    /**
     * AJAX endpoint for searching tenants
     */
    public function search(Request $request): \Illuminate\Http\JsonResponse
    {
        $search = $request->get('search');
        $tenants = Tenant::query()
            ->where('status', '!=', 'blacklisted')
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->get(['id', 'uuid', 'name']);

        return response()->json($tenants);
    }
}
