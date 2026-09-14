<?php

namespace App\Modules\Reports\Services;

use App\Modules\Accounting\Models\Account;
use Illuminate\Support\Carbon;

/**
 * Reuses Account::balance()'s own debit/credit-normal sign convention
 * (ARCHITECTURE.md §12) rather than reimplementing it - balance() prefers
 * `debit_total`/`credit_total` when already present on the model, so
 * aliasing this report's date-scoped `withSum()` calls to those exact
 * names makes an ordinary all-time balance() call become a date-ranged
 * one for free, with zero duplicated sign logic.
 */
class IncomeStatementReportService
{
    public function generate(Carbon $startDate, Carbon $endDate): array
    {
        $accounts = Account::query()
            ->whereIn('type', ['income', 'expense'])
            ->withSum(['lines as debit_total' => fn ($query) => $query->whereHas(
                'journalEntry',
                fn ($journalEntryQuery) => $journalEntryQuery->whereBetween('entry_date', [$startDate, $endDate])
            )], 'debit')
            ->withSum(['lines as credit_total' => fn ($query) => $query->whereHas(
                'journalEntry',
                fn ($journalEntryQuery) => $journalEntryQuery->whereBetween('entry_date', [$startDate, $endDate])
            )], 'credit')
            ->orderBy('code')
            ->get();

        $toRow = fn (Account $account) => ['code' => $account->code, 'name' => $account->name, 'amount' => $account->balance()];

        $income = $accounts->where('type', 'income')->map($toRow)->values();
        $expenses = $accounts->where('type', 'expense')->map($toRow)->values();
        $totalIncome = $income->sum('amount');
        $totalExpenses = $expenses->sum('amount');

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'income' => $income,
            'expenses' => $expenses,
            'total_income' => $totalIncome,
            'total_expenses' => $totalExpenses,
            'net_income' => $totalIncome - $totalExpenses,
        ];
    }
}
