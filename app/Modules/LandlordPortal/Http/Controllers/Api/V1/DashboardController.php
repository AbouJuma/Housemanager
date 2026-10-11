<?php

namespace App\Modules\LandlordPortal\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Invoices\Models\Invoice;
use App\Modules\Maintenance\Http\Resources\MaintenanceRequestResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * JSON mirror of App\Modules\LandlordPortal\Http\Controllers\
 * LandlordPortalDashboardController - identical queries/shape.
 */
class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $landlord = $request->user();

        $unitCounts = ['total' => 0, 'occupied' => 0, 'vacant' => 0];
        foreach ($landlord->properties()->with('units')->get() as $property) {
            foreach ($property->units as $unit) {
                $unitCounts['total']++;
                $unitCounts[$unit->isVacant() ? 'vacant' : 'occupied']++;
            }
        }

        $totalCollected = Invoice::whereHas(
            'lease.unit.building.property',
            fn ($query) => $query->where('landlord_id', $landlord->id)
        )->sum('amount_paid');

        $totalExpenses = $landlord->expenses()->where('expenses.status', 'active')->sum('amount');

        $recentMaintenanceRequests = $landlord->maintenanceRequests()->latest('reported_date')->limit(5)->get();

        return response()->json([
            'unit_counts' => $unitCounts,
            'total_collected' => (float) $totalCollected,
            'total_expenses' => (float) $totalExpenses,
            'net_amount' => (float) $totalCollected - (float) $totalExpenses,
            'recent_maintenance_requests' => MaintenanceRequestResource::collection($recentMaintenanceRequests),
        ]);
    }
}
