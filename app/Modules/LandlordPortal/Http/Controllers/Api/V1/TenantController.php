<?php

namespace App\Modules\LandlordPortal\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\LandlordPortal\Http\Requests\LandlordTenantRequest;
use App\Modules\LandlordPortal\Services\LandlordManagementService;
use App\Modules\LandlordPortal\Support\LandlordTenantPresenter;
use App\Modules\Tenants\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** The landlord's tenants in the app - list, detail and add. */
class TenantController extends Controller
{
    public function __construct(protected LandlordManagementService $service) {}

    public function index(Request $request): JsonResponse
    {
        $landlord = $request->user();
        $search = $request->string('search')->trim()->toString();
        $activeHere = fn (Builder $lease) => $lease->where('status', 'active')
            ->whereHas('unit.building.property', fn (Builder $p) => $p->where('landlord_id', $landlord->id));

        $page = $landlord->tenantsQuery()
            ->with(LandlordTenantPresenter::with($landlord))
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")->orWhere('username', 'like', "%{$search}%")))
            ->when($request->query('status') === 'with_lease', fn ($q) => $q->whereHas('leases', $activeHere))
            ->when($request->query('status') === 'no_lease', fn ($q) => $q->whereDoesntHave('leases', $activeHere))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return response()->json([
            'data' => $page->getCollection()->map(fn (Tenant $tenant) => LandlordTenantPresenter::row($tenant, $landlord))->values(),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
        ]);
    }

    public function show(Request $request, Tenant $tenant): JsonResponse
    {
        $landlord = $request->user();
        abort_unless($landlord->tenantsQuery()->whereKey($tenant->id)->exists(), 404);

        $tenant->load(LandlordTenantPresenter::with($landlord));

        return response()->json(['data' => LandlordTenantPresenter::detail($tenant, $landlord)]);
    }

    public function store(LandlordTenantRequest $request): JsonResponse
    {
        $landlord = $request->user();
        [$tenant, $password] = $this->service->createTenant($landlord, $request->validated());
        $tenant->load(LandlordTenantPresenter::with($landlord));

        return response()->json([
            'data' => LandlordTenantPresenter::detail($tenant, $landlord),
            // Shown once so the landlord can pass it on; the tenant is also sent it.
            'login' => ['username' => $tenant->username, 'password' => $password],
        ], 201);
    }
}
