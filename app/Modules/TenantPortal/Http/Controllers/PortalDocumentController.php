<?php

namespace App\Modules\TenantPortal\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Documents\Models\Document;
use App\Modules\Leases\Models\Lease;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PortalDocumentController extends Controller
{
    /**
     * A tenant's documents come from two places: attached directly to
     * them (an ID copy, a signed application) or to one of their leases
     * (the lease agreement itself) - merged into one list rather than
     * making the tenant guess which tab to check.
     */
    public function index(): View
    {
        $tenant = Auth::guard('tenant')->user();
        $leaseIds = $tenant->leases()->pluck('id');

        $documents = $tenant->documents()
            ->get()
            ->merge(
                Document::where('documentable_type', Lease::class)->whereIn('documentable_id', $leaseIds)->get()
            )
            ->sortByDesc('created_at')
            ->values();

        return view('tenant-portal::documents.index', ['documents' => $documents]);
    }
}
