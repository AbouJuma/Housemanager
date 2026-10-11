<?php

namespace App\Modules\TenantPortal\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Documents\Http\Resources\DocumentResource;
use App\Modules\Documents\Models\Document;
use App\Modules\Leases\Models\Lease;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * JSON mirror of App\Modules\TenantPortal\Http\Controllers\
 * PortalDocumentController - same "merge documents attached directly to
 * the tenant with documents attached to their leases" shape. Paginated
 * manually (15/page) since the merge produces a plain Collection, not
 * an Eloquent query, so ->paginate() isn't available directly.
 */
class DocumentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $tenant = $request->user();
        $leaseIds = $tenant->leases()->pluck('id');

        $documents = $tenant->documents()
            ->get()
            ->merge(
                Document::where('documentable_type', Lease::class)->whereIn('documentable_id', $leaseIds)->get()
            )
            ->sortByDesc('created_at')
            ->values();

        $page = $request->integer('page', 1);
        $perPage = 15;
        $paginated = new LengthAwarePaginator(
            $documents->forPage($page, $perPage)->values(),
            $documents->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return DocumentResource::collection($paginated);
    }
}
