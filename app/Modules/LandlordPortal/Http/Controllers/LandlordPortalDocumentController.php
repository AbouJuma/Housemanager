<?php

namespace App\Modules\LandlordPortal\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Documents\Models\Document;
use App\Modules\Properties\Models\Property;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LandlordPortalDocumentController extends Controller
{
    /**
     * Same "merge two sources" shape as App\Modules\TenantPortal\Http\
     * Controllers\PortalDocumentController (Module 26) - documents
     * attached directly to the landlord, plus documents attached to any
     * of their own properties (a title deed, most commonly).
     */
    public function index(): View
    {
        $landlord = Auth::guard('landlord')->user();
        $propertyIds = $landlord->properties()->pluck('id');

        $documents = $landlord->documents()
            ->get()
            ->merge(
                Document::where('documentable_type', Property::class)->whereIn('documentable_id', $propertyIds)->get()
            )
            ->sortByDesc('created_at')
            ->values();

        return view('landlord-portal::documents.index', ['documents' => $documents]);
    }
}
