<?php

namespace App\Modules\Expenses\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Accounting\Models\Account;
use App\Modules\Expenses\Http\Requests\MarkExpensePaidRequest;
use App\Modules\Expenses\Http\Requests\StoreExpenseRequest;
use App\Modules\Expenses\Http\Requests\UpdateExpenseRequest;
use App\Modules\Expenses\Models\Expense;
use App\Modules\Expenses\Services\ExpenseService;
use App\Modules\Properties\Models\Property;
use App\Modules\Vendors\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function __construct(protected ExpenseService $service)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Expense::class);

        $expenses = $this->service->paginate(
            $request->string('search')->toString() ?: null,
            $request->integer('property_id') ?: null,
            $request->string('payment_status')->toString() ?: null,
        );

        return view('expenses::index', [
            'expenses' => $expenses,
            'properties' => $this->propertyOptions(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Expense::class);

        return view('expenses::create', [
            'accounts' => $this->expenseAccountOptions(),
            'properties' => $this->propertyOptions(),
            'vendors' => $this->vendorOptions(),
        ]);
    }

    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        $expense = $this->service->create([
            ...$request->validated(),
            'company_id' => $request->user()->company_id,
            'recorded_by_id' => $request->user()->id,
        ]);

        return redirect()->route('expenses.show', $expense)->with('success', 'Expense recorded.');
    }

    public function show(Expense $expense): View
    {
        $this->authorize('view', $expense);

        return view('expenses::show', [
            'expense' => $expense->load(['account', 'property', 'vendor', 'recordedBy']),
        ]);
    }

    public function edit(Expense $expense): View
    {
        $this->authorize('update', $expense);

        return view('expenses::edit', [
            'expense' => $expense,
            'properties' => $this->propertyOptions(),
            'vendors' => $this->vendorOptions(),
        ]);
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $this->service->update($expense, $request->validated());

        return redirect()->route('expenses.show', $expense)->with('success', 'Expense updated.');
    }

    public function markPaid(MarkExpensePaidRequest $request, Expense $expense): RedirectResponse
    {
        $this->service->markAsPaid($expense, $request->validated());

        return redirect()->route('expenses.show', $expense)->with('success', 'Expense marked as paid.');
    }

    public function void(Request $request, Expense $expense): RedirectResponse
    {
        $this->authorize('update', $expense);

        $this->service->void($expense, $request->string('reason')->toString() ?: null);

        return redirect()->route('expenses.show', $expense)->with('success', 'Expense voided.');
    }

    protected function expenseAccountOptions(): \Illuminate\Support\Collection
    {
        return Account::query()->where('type', 'expense')->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']);
    }

    protected function propertyOptions(): \Illuminate\Support\Collection
    {
        return Property::query()->orderBy('name')->get(['id', 'uuid', 'name']);
    }

    protected function vendorOptions(): \Illuminate\Support\Collection
    {
        return Vendor::query()->where('status', 'active')->orderBy('name')->get(['id', 'uuid', 'name']);
    }
}
