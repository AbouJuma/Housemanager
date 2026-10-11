<?php

namespace App\Modules\LandlordPortal\Http\Controllers\Api\V1;

use App\Core\Http\Concerns\HasListFilters;
use App\Http\Controllers\Controller;
use App\Modules\Expenses\Http\Resources\ExpenseResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * JSON mirror of App\Modules\LandlordPortal\Http\Controllers\
 * LandlordPortalExpenseController - paginated (15/page) with optional
 * ?status=&from=&to= (date range against expense_date).
 */
class ExpenseController extends Controller
{
    use HasListFilters;

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = $request->user()->expenses()->with(['account', 'property']);
        $query = $this->applyStatusFilter($query, $request, 'payment_status');
        $query = $this->applyDateRangeFilter($query, $request, 'expense_date');

        return ExpenseResource::collection($query->latest('expense_date')->paginate(15)->withQueryString());
    }
}
