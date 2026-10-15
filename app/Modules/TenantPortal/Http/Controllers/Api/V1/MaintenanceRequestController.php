<?php

namespace App\Modules\TenantPortal\Http\Controllers\Api\V1;

use App\Core\Http\Concerns\HasListFilters;
use App\Http\Controllers\Controller;
use App\Modules\Maintenance\Http\Resources\MaintenanceRequestResource;
use App\Modules\Maintenance\Models\MaintenanceRequest;
use App\Modules\Maintenance\Services\MaintenanceRequestService;
use App\Modules\TenantPortal\Http\Requests\Api\V1\StoreMaintenanceRequestRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * JSON mirror of App\Modules\TenantPortal\Http\Controllers\
 * PortalMaintenanceRequestController - now including store(), reusing
 * the exact same PortalStoreMaintenanceRequestRequest validation the
 * web portal uses (property/unit/tenant are always derived from the
 * tenant's own lease server-side, never client-supplied - see that
 * request class's docblock for why). index() is paginated (15/page)
 * with optional ?status=&from=&to= (date range against reported_date).
 */
class MaintenanceRequestController extends Controller
{
    use HasListFilters;

    public function __construct(protected MaintenanceRequestService $service) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = MaintenanceRequest::where('tenant_id', $request->user()->id)->with('property', 'vendor');
        $query = $this->applyStatusFilter($query, $request);
        $query = $this->applyDateRangeFilter($query, $request, 'reported_date');

        return MaintenanceRequestResource::collection($query->latest('reported_date')->paginate(15)->withQueryString());
    }

    public function show(Request $request, MaintenanceRequest $maintenanceRequest): MaintenanceRequestResource
    {
        abort_unless(same_id($maintenanceRequest->tenant_id, $request->user()->id), 403);

        return MaintenanceRequestResource::make($maintenanceRequest->load('property', 'unit', 'vendor'));
    }

    public function store(StoreMaintenanceRequestRequest $request): MaintenanceRequestResource
    {
        $tenant = $request->user();
        // By uuid, not findOrFail() (which keys on the numeric id) -
        // the request validates lease_id against the uuid column too,
        // see StoreMaintenanceRequestRequest's docblock for why.
        $lease = $tenant->leases()->with('unit')->where('uuid', $request->validated('lease_id'))->firstOrFail();

        $maintenanceRequest = $this->service->create([
            ...$request->validated(),
            'company_id' => $tenant->company_id,
            'property_id' => $lease->unit->building->property_id,
            'unit_id' => $lease->unit_id,
            'tenant_id' => $tenant->id,
            'reported_date' => now()->toDateString(),
        ]);

        return MaintenanceRequestResource::make($maintenanceRequest);
    }
}
