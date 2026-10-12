<?php

namespace App\Modules\LandlordPortal\Http\Controllers;

use App\Core\Support\Period;
use App\Http\Controllers\Controller;
use App\Modules\Accounting\Models\Account;
use App\Modules\Expenses\Models\Expense;
use App\Modules\LandlordPortal\Http\Requests\LandlordExpenseRequest;
use App\Modules\LandlordPortal\Services\LandlordManagementService;
use App\Modules\LandlordPortal\Support\LandlordAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LandlordPortalExpenseController extends Controller
{
    public function __construct(protected LandlordManagementService $service) {}

    public function index(Request $request): View
    {
        $landlord = Auth::guard('landlord')->user();
        // Everything until a period or dates are picked.
        $filtered = $request->filled('period') || $request->filled('from') || $request->filled('to');
        [$from, $to, $period] = $filtered ? Period::resolve($request->query('period'), $request->query('from'), $request->query('to')) : [null, null, null];
        $status = $request->query('status');
        $propertyId = $request->integer('property') ?: null;

        $query = $landlord->expenses()
            ->with(['account', 'property'])
            ->when($from, fn ($q) => $q->whereDate('expense_date', '>=', $from->toDateString())->whereDate('expense_date', '<=', $to->toDateString()))
            ->when(in_array($status, ['paid', 'unpaid'], true), fn ($q) => $q->where('payment_status', $status))
            ->when($propertyId, fn ($q) => $q->where('expenses.property_id', $propertyId));

        $expenses = (clone $query)->latest('expense_date')->paginate(25)->withQueryString();
        $counted = (clone $query)->where('expenses.status', '!=', 'voided');

        return view('landlord-portal::expenses.index', [
            'expenses' => $expenses,
            'total' => (float) (clone $counted)->sum('amount'),
            'unpaid' => (float) (clone $counted)->where('payment_status', 'unpaid')->sum('amount'),
            'properties' => $landlord->properties()->orderBy('name')->get(['id', 'name']),
            'period' => $period,
            'from' => $from?->toDateString(),
            'to' => $to?->toDateString(),
            'status' => $status,
            'propertyId' => $propertyId,
            'canCreate' => LandlordAccess::can($landlord, 'expenses.create'),
            'canUpdate' => LandlordAccess::can($landlord, 'expenses.update'),
        ]);
    }

    public function create(): View
    {
        $landlord = Auth::guard('landlord')->user();
        LandlordAccess::authorize($landlord, 'expenses.create');

        return view('landlord-portal::expenses.form', $this->formData($landlord) + ['expense' => null]);
    }

    public function store(LandlordExpenseRequest $request): RedirectResponse
    {
        $this->service->createExpense($request->user(), $request->validated());

        return redirect()->route('landlord-portal.expenses.index')->with('success', __('Expense recorded.'));
    }

    public function edit(Expense $expense): View
    {
        $landlord = Auth::guard('landlord')->user();
        abort_unless($expense->property?->landlord_id === $landlord->id && $expense->status !== 'voided', 403);
        LandlordAccess::authorize($landlord, 'expenses.update');

        return view('landlord-portal::expenses.form', $this->formData($landlord) + ['expense' => $expense]);
    }

    public function update(LandlordExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $this->service->updateExpense($expense, $request->validated());

        return redirect()->route('landlord-portal.expenses.index')->with('success', __('Expense updated.'));
    }

    protected function formData($landlord): array
    {
        return [
            'properties' => $landlord->properties()->orderBy('name')->get(['id', 'name']),
            'categories' => Account::query()->where('company_id', $landlord->company_id)->where('type', 'expense')
                ->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']),
        ];
    }
}
