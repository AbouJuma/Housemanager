<?php

namespace App\Modules\BuyerPortal\Http\Controllers\Api\V1;

use App\Core\Http\Concerns\HasListFilters;
use App\Http\Controllers\Controller;
use App\Modules\Sales\Http\Resources\SaleResource;
use App\Modules\Sales\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * index() is paginated (15/page) with optional ?status=&from=&to=
 * (date range against sale_date).
 */
class SaleController extends Controller
{
    use HasListFilters;

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = $request->user()->sales()->with('unit.building.property');
        $query = $this->applyStatusFilter($query, $request);
        $query = $this->applyDateRangeFilter($query, $request, 'sale_date');

        return SaleResource::collection($query->latest('sale_date')->paginate(15)->withQueryString());
    }

    public function show(Request $request, Sale $sale): SaleResource
    {
        abort_unless($sale->new_buyer_id === $request->user()->id, 403);

        return SaleResource::make($sale->load(['unit.building.property', 'installments']));
    }
}
