<?php

namespace App\Modules\TenantPortal\Http\Controllers\Api\V1;

use App\Core\Http\Concerns\HasListFilters;
use App\Http\Controllers\Controller;
use App\Modules\Leases\Http\Resources\LeaseResource;
use App\Modules\Leases\Models\Lease;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * JSON mirror of App\Modules\TenantPortal\Http\Controllers\
 * PortalLeaseController - paginated (15/page) with optional
 * ?status=&from=&to= (date range against start_date), for accounts
 * with years of lease history.
 */
class LeaseController extends Controller
{
    use HasListFilters;

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = $request->user()->leases()->with('unit.building.property');
        $query = $this->applyStatusFilter($query, $request);
        $query = $this->applyDateRangeFilter($query, $request, 'start_date');

        return LeaseResource::collection($query->latest('start_date')->paginate(15)->withQueryString());
    }

    public function show(Request $request, Lease $lease): LeaseResource
    {
        abort_unless(same_id($lease->tenant_id, $request->user()->id), 403);

        return LeaseResource::make($lease->load(['unit.building.property', 'invoices']));
    }
}
