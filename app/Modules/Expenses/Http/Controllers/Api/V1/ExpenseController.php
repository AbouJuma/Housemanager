<?php

namespace App\Modules\Expenses\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Expenses\Http\Requests\MarkExpensePaidRequest;
use App\Modules\Expenses\Http\Requests\StoreExpenseRequest;
use App\Modules\Expenses\Http\Requests\UpdateExpenseRequest;
use App\Modules\Expenses\Http\Resources\ExpenseResource;
use App\Modules\Expenses\Models\Expense;
use App\Modules\Expenses\Services\ExpenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ExpenseController extends Controller
{
    public function __construct(protected ExpenseService $service)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Expense::class);

        return ExpenseResource::collection(
            $this->service->paginate(
                $request->string('search')->toString() ?: null,
                $request->integer('property_id') ?: null,
                $request->string('payment_status')->toString() ?: null,
            )
        );
    }

    public function store(StoreExpenseRequest $request): JsonResponse
    {
        $expense = $this->service->create([
            ...$request->validated(),
            'company_id' => $request->user()->company_id,
            'recorded_by_id' => $request->user()->id,
        ]);

        return ExpenseResource::make($expense->load(['account', 'property', 'vendor']))->response()->setStatusCode(201);
    }

    public function show(Expense $expense): ExpenseResource
    {
        $this->authorize('view', $expense);

        return ExpenseResource::make($expense->load(['account', 'property', 'vendor', 'recordedBy']));
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): ExpenseResource
    {
        $expense = $this->service->update($expense, $request->validated());

        return ExpenseResource::make($expense->load(['account', 'property', 'vendor']));
    }

    public function markPaid(MarkExpensePaidRequest $request, Expense $expense): ExpenseResource
    {
        $expense = $this->service->markAsPaid($expense, $request->validated());

        return ExpenseResource::make($expense->load(['account', 'property', 'vendor']));
    }

    public function void(Request $request, Expense $expense): ExpenseResource
    {
        $this->authorize('update', $expense);

        $expense = $this->service->void($expense, $request->string('reason')->toString() ?: null);

        return ExpenseResource::make($expense->load(['account', 'property', 'vendor']));
    }
}
