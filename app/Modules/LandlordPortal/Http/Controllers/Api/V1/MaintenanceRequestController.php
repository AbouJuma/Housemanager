<?php

namespace App\Modules\LandlordPortal\Http\Controllers\Api\V1;

use App\Core\Http\Concerns\HasListFilters;
use App\Http\Controllers\Controller;
use App\Modules\Maintenance\Http\Resources\MaintenanceRequestResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * JSON mirror of App\Modules\LandlordPortal\Http\Controllers\
 * LandlordPortalMaintenanceRequestController - paginated (15/page) with
 * optional ?status=&from=&to= (date range against reported_date).
 */
class MaintenanceRequestController extends Controller
{
    use HasListFilters;

    public function index(Request $request): AnonymousResourceCollection
    {
        // 'maintenance_requests.status', not just 'status' - this is a
        // HasManyThrough (Landlord -> Property -> MaintenanceRequest)
        // and both intermediate tables have their own status column,
        // the same ambiguous-column trap the dashboard controller's own
        // docblock already documents.
        $query = $request->user()->maintenanceRequests()->with(['property', 'vendor']);
        $query = $this->applyStatusFilter($query, $request, 'maintenance_requests.status');
        $query = $this->applyDateRangeFilter($query, $request, 'reported_date');

        return MaintenanceRequestResource::collection($query->latest('reported_date')->paginate(15)->withQueryString());
    }
}
