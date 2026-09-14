<?php

namespace App\Modules\Maintenance\Services;

use App\Core\Services\BaseService;
use App\Modules\Expenses\Services\ExpenseService;
use App\Modules\Maintenance\Models\MaintenanceRequest;
use App\Modules\Maintenance\Repositories\MaintenanceRequestRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * @property MaintenanceRequestRepositoryInterface $repository see App\Modules\Companies\Services\CompanyService
 *  for why this can't be redeclared as a typed constructor property.
 */
class MaintenanceRequestService extends BaseService
{
    public function __construct(
        MaintenanceRequestRepositoryInterface $repository,
        protected ExpenseService $expenseService,
    ) {
        parent::__construct($repository);
    }

    public function paginate(?string $search, ?int $propertyId, ?string $status, ?string $priority, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->query()
            ->with(['property', 'unit', 'vendor'])
            ->when($search, fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('title', 'like', "%{$search}%")->orWhere('description', 'like', "%{$search}%");
            }))
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($priority, fn ($query) => $query->where('priority', $priority))
            ->latest('reported_date')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * completed_date is derived, not form input, the moment status
     * actually transitions to 'completed' - the same discipline as
     * invoices.terminated_at/leases-style system-set dates elsewhere,
     * unless the caller explicitly backdated it.
     */
    public function update(Model $model, array $attributes): Model
    {
        $becomingCompleted = $model->status !== 'completed' && ($attributes['status'] ?? $model->status) === 'completed';

        if ($becomingCompleted && empty($attributes['completed_date'])) {
            $attributes['completed_date'] = now()->toDateString();
        }

        return parent::update($model, $attributes);
    }

    /**
     * Creates a real Expense through ExpenseService (so it posts to
     * the Accounting ledger exactly the same way any other expense
     * does - ARCHITECTURE.md §13/§15), pre-filled from this request's
     * property/vendor, then links it back via expense_id. Deliberately
     * a manual, explicit action (a button the user clicks), not
     * automatic on marking a request completed - a maintenance record
     * shouldn't silently spend money without the user confirming an
     * actual amount.
     */
    public function recordExpense(MaintenanceRequest $request, array $attributes): MaintenanceRequest
    {
        if ($request->hasRecordedExpense()) {
            throw ValidationException::withMessages(['expense_id' => 'This request already has a recorded expense.']);
        }

        return DB::transaction(function () use ($request, $attributes) {
            $expense = $this->expenseService->create([
                'company_id' => $request->company_id,
                'account_id' => $attributes['account_id'],
                'property_id' => $request->property_id,
                'vendor_id' => $request->vendor_id,
                'description' => "Maintenance: {$request->title}",
                'amount' => $attributes['amount'],
                'expense_date' => $attributes['expense_date'],
                'payment_status' => $attributes['payment_status'],
                'payment_method' => $attributes['payment_method'] ?? null,
                'paid_on' => $attributes['paid_on'] ?? null,
                'recorded_by_id' => $attributes['recorded_by_id'] ?? null,
            ]);

            $request->update(['expense_id' => $expense->id, 'actual_cost' => $expense->amount]);

            return $request->fresh();
        });
    }
}
