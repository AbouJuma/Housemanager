<?php

namespace App\Modules\LandlordPortal\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Documents\Http\Resources\DocumentResource;
use App\Modules\Documents\Models\Document;
use App\Modules\Properties\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * JSON mirror of App\Modules\LandlordPortal\Http\Controllers\
 * LandlordPortalDocumentController. Paginated manually (15/page), same
 * reasoning as App\Modules\TenantPortal\Http\Controllers\Api\V1\
 * DocumentController - the merge produces a plain Collection, not an
 * Eloquent query.
 */
class DocumentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $landlord = $request->user();
        $propertyIds = $landlord->properties()->pluck('id');

        $documents = $landlord->documents()
            ->get()
            ->merge(
                Document::where('documentable_type', Property::class)->whereIn('documentable_id', $propertyIds)->get()
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
