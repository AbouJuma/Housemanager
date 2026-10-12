<?php

namespace App\Modules\LandlordPortal\Http\Controllers\Api\V1;

use App\Core\Http\Concerns\HasListFilters;
use App\Core\Support\Period;
use App\Http\Controllers\Controller;
use App\Modules\Accounting\Models\Account;
use App\Modules\Expenses\Http\Resources\ExpenseResource;
use App\Modules\Expenses\Models\Expense;
use App\Modules\LandlordPortal\Http\Requests\LandlordExpenseRequest;
use App\Modules\LandlordPortal\Services\LandlordManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * The landlord's expenses in the app - paginated (15/page) with
 * ?status= (payment status), ?period=today|this_week|this_month|... or
 * ?from=&to= (expense date), ?property= (uuid); add and edit.
 */
class ExpenseController extends Controller
{
    use HasListFilters;

    public function __construct(protected LandlordManagementService $service) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = $request->user()->expenses()->with(['account', 'property']);
        $query = $this->applyStatusFilter($query, $request, 'payment_status');

        if ($request->filled('period')) {
            [$from, $to] = Period::resolve($request->query('period'));
            $query->whereDate('expense_date', '>=', $from->toDateString())->whereDate('expense_date', '<=', $to->toDateString());
        } else {
            $query = $this->applyDateRangeFilter($query, $request, 'expense_date');
        }
        if ($request->filled('property')) {
            $query->whereHas('property', fn ($q) => $q->where('uuid', $request->query('property')));
        }

        $counted = (clone $query)->where('expenses.status', '!=', 'voided');

        return ExpenseResource::collection($query->latest('expense_date')->paginate(15)->withQueryString())
            ->additional(['summary' => [
                'total' => (float) (clone $counted)->sum('amount'),
                'unpaid' => (float) (clone $counted)->where('payment_status', 'unpaid')->sum('amount'),
            ]]);
    }

    public function store(LandlordExpenseRequest $request): JsonResponse
    {
        $expense = $this->service->createExpense($request->user(), $request->validated());

        return ExpenseResource::make($expense->load(['account', 'property']))->response()->setStatusCode(201);
    }

    public function update(LandlordExpenseRequest $request, Expense $expense): ExpenseResource
    {
        $expense = $this->service->updateExpense($expense, $request->validated());

        return ExpenseResource::make($expense->load(['account', 'property']));
    }

    /** Properties and expense categories for the app's form. */
    public function options(Request $request): JsonResponse
    {
        $landlord = $request->user();

        return response()->json([
            'properties' => $landlord->properties()->orderBy('name')->get(['id', 'uuid', 'name']),
            'categories' => Account::query()->where('company_id', $landlord->company_id)->where('type', 'expense')
                ->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']),
            'payment_methods' => LandlordExpenseRequest::METHODS,
        ]);
    }
}
