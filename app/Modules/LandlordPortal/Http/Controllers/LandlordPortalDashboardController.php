<?php

namespace App\Modules\LandlordPortal\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Invoices\Models\Invoice;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LandlordPortalDashboardController extends Controller
{
    public function index(): View
    {
        $landlord = Auth::guard('landlord')->user();

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

        // 'expenses.', not just 'status' - both `expenses` and
        // `properties` (this HasManyThrough's intermediate table) have a
        // `status` column, the same ambiguous-column trap already caught
        // by the test suite for tenants.invoices() in Module 26
        // (ARCHITECTURE.md §19).
        $totalExpenses = $landlord->expenses()->where('expenses.status', 'active')->sum('amount');

        $recentMaintenanceRequests = $landlord->maintenanceRequests()->latest('reported_date')->limit(5)->get();

        return view('landlord-portal::dashboard', [
            'landlord' => $landlord,
            'properties' => $landlord->properties,
            'unitCounts' => $unitCounts,
            'totalCollected' => (float) $totalCollected,
            'totalExpenses' => (float) $totalExpenses,
            'netAmount' => (float) $totalCollected - (float) $totalExpenses,
            'recentMaintenanceRequests' => $recentMaintenanceRequests,
        ]);
    }
}
