<?php

namespace App\Modules\Expenses\Services;

use App\Core\Services\BaseService;
use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Accounting\Services\JournalEntryService;
use App\Modules\Expenses\Models\Expense;
use App\Modules\Expenses\Repositories\ExpenseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * @property ExpenseRepositoryInterface $repository see App\Modules\Companies\Services\CompanyService
 *  for why this can't be redeclared as a typed constructor property.
 */
class ExpenseService extends BaseService
{
    public function __construct(
        ExpenseRepositoryInterface $repository,
        protected JournalEntryService $journalEntryService,
    ) {
        parent::__construct($repository);
    }

    public function paginate(?string $search, ?int $propertyId, ?string $paymentStatus, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->query()
            ->with(['account', 'property', 'vendor'])
            ->when($search, fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('description', 'like', "%{$search}%")
                    ->orWhere('vendor_name', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%");
            }))
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->when($paymentStatus, fn ($query) => $query->where('payment_status', $paymentStatus))
            ->latest('expense_date')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Posts the expense's initial journal entry as part of creation -
     * unlike Invoice's multi-stage draft/send lifecycle, an expense
     * isn't a document sent to a third party for approval, so there's
     * no reason to defer posting to a separate step.
     */
    public function create(array $attributes): Model
    {
        return DB::transaction(function () use ($attributes) {
            $expense = $this->repository->create($attributes);
            $this->postInitialEntry($expense);

            return $expense;
        });
    }

    /**
     * There is no delete() for an expense once it exists - it always
     * has at least one posted journal entry (see create()), and
     * hard-deleting the expense while leaving that entry behind would
     * orphan real ledger history. void() is the only way to retract
     * one, the same discipline JournalEntry itself follows
     * (ARCHITECTURE.md §12/§13).
     */
    public function delete(Model $model): bool
    {
        throw ValidationException::withMessages(['status' => 'Expenses cannot be deleted - void this expense instead.']);
    }

    public function markAsPaid(Expense $expense, array $attributes): Expense
    {
        if ($expense->isVoided()) {
            throw ValidationException::withMessages(['status' => 'A voided expense cannot be marked as paid.']);
        }

        if ($expense->isPaid()) {
            throw ValidationException::withMessages(['status' => 'This expense is already marked as paid.']);
        }

        return DB::transaction(function () use ($expense, $attributes) {
            $this->postSettlementEntry($expense, $attributes['payment_method'], $attributes['paid_on']);

            $expense->update([
                'payment_status' => 'paid',
                'payment_method' => $attributes['payment_method'],
                'paid_on' => $attributes['paid_on'],
            ]);

            return $expense->fresh();
        });
    }

    /**
     * Voids every journal entry this expense ever posted (the initial
     * one, and the settlement one if it was later marked paid) rather
     * than just the most recent - an expense that was marked paid and
     * is then voided needs both halves of its ledger history reversed,
     * not just the last action taken.
     */
    public function void(Expense $expense, ?string $reason = null): Expense
    {
        if ($expense->isVoided()) {
            throw ValidationException::withMessages(['status' => 'This expense is already voided.']);
        }

        return DB::transaction(function () use ($expense, $reason) {
            JournalEntry::query()
                ->where('source_type', Expense::class)
                ->where('source_id', $expense->id)
                ->where('status', 'posted')
                ->get()
                ->each(fn (JournalEntry $entry) => $this->journalEntryService->void($entry, $reason));

            $expense->update(['status' => 'voided']);

            return $expense->fresh();
        });
    }

    /**
     * Fails soft, never hard - same discipline as Accounting's
     * InvoiceSent/InvoicePaymentRecorded listeners (ARCHITECTURE.md
     * §12): if a company's default Accounts Payable/Cash/Bank account
     * is missing, renamed, or inactive, this logs a warning and skips
     * the posting rather than blocking the expense from being
     * recorded at all.
     */
    protected function postInitialEntry(Expense $expense): void
    {
        $counterAccount = $expense->payment_status === 'paid'
            ? $this->cashOrBankAccount($expense->company_id, $expense->payment_method ?? 'cash')
            : $this->account($expense->company_id, '2000');

        if (! $counterAccount) {
            Log::warning("Expenses: skipped automatic posting for expense \"{$expense->description}\" - default Accounts Payable/Cash/Bank account not found or inactive for company {$expense->company_id}.");

            return;
        }

        $this->post($expense, $expense->expense_date->toDateString(), $expense->description, $counterAccount);
    }

    protected function postSettlementEntry(Expense $expense, string $paymentMethod, string $paidOn): void
    {
        $payable = $this->account($expense->company_id, '2000');
        $cashAccount = $this->cashOrBankAccount($expense->company_id, $paymentMethod);

        if (! $payable || ! $cashAccount) {
            Log::warning("Expenses: skipped settlement posting for expense \"{$expense->description}\" - default Accounts Payable/Cash/Bank account not found or inactive for company {$expense->company_id}.");

            return;
        }

        try {
            $this->journalEntryService->post([
                'company_id' => $expense->company_id,
                'entry_date' => $paidOn,
                'reference' => $expense->reference,
                'description' => "Settled: {$expense->description}",
                'source_type' => Expense::class,
                'source_id' => $expense->id,
                'lines' => [
                    ['account_id' => $payable->id, 'debit' => (float) $expense->amount],
                    ['account_id' => $cashAccount->id, 'credit' => (float) $expense->amount],
                ],
            ]);
        } catch (Throwable $e) {
            Log::error("Expenses: failed to post settlement journal entry for expense \"{$expense->description}\": {$e->getMessage()}");
        }
    }

    protected function post(Expense $expense, string $entryDate, string $description, Account $counterAccount): void
    {
        try {
            $this->journalEntryService->post([
                'company_id' => $expense->company_id,
                'entry_date' => $entryDate,
                'reference' => $expense->reference,
                'description' => $description,
                'source_type' => Expense::class,
                'source_id' => $expense->id,
                'lines' => [
                    ['account_id' => $expense->account_id, 'debit' => (float) $expense->amount],
                    ['account_id' => $counterAccount->id, 'credit' => (float) $expense->amount],
                ],
            ]);
        } catch (Throwable $e) {
            Log::error("Expenses: failed to post journal entry for expense \"{$expense->description}\": {$e->getMessage()}");
        }
    }

    protected function account(int $companyId, string $code): ?Account
    {
        return Account::query()->forCompany($companyId)->where('code', $code)->where('is_active', true)->first();
    }

    /**
     * bank_transfer/cheque settle into a bank account, not a physical
     * till - cash/mobile_money/other default to Cash. Same mapping
     * PostInvoicePaymentJournalEntry uses (App\Modules\RentCollection),
     * duplicated rather than shared - the two modules have no other
     * reason to depend on each other, and it's a single ternary, not
     * worth a cross-module dependency to deduplicate.
     */
    protected function cashOrBankAccount(int $companyId, string $paymentMethod): ?Account
    {
        $code = in_array($paymentMethod, ['bank_transfer', 'cheque'], true) ? '1100' : '1000';

        return $this->account($companyId, $code);
    }
}
