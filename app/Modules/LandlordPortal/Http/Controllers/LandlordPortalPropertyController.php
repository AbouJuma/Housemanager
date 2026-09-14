<?php

namespace App\Modules\LandlordPortal\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Properties\Models\Property;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LandlordPortalPropertyController extends Controller
{
    public function index(): View
    {
        $landlord = Auth::guard('landlord')->user();

        return view('landlord-portal::properties.index', [
            'properties' => $landlord->properties()->withCount('buildings')->get(),
        ]);
    }

    public function show(Property $property): View
    {
        $landlord = Auth::guard('landlord')->user();
        abort_unless($property->landlord_id === $landlord->id, 403);

        return view('landlord-portal::properties.show', [
            'property' => $property->load('buildings.units'),
        ]);
    }
}
