<?php

namespace App\Modules\LandlordPortal\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LandlordPortalMaintenanceRequestController extends Controller
{
    public function index(): View
    {
        $landlord = Auth::guard('landlord')->user();

        return view('landlord-portal::maintenance-requests.index', [
            // 'maintenance_requests.', not just 'reported_date' would be
            // unambiguous on its own, but this file follows the same
            // "qualify columns reached through a HasManyThrough's
            // intermediate `properties` table" discipline as the
            // dashboard controller, since both tables involved here have
            // their own status column.
            'requests' => $landlord->maintenanceRequests()->with(['property', 'vendor'])->latest('reported_date')->get(),
        ]);
    }
}
