<?php

namespace Tests\Feature\Expenses;

use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Companies\Models\Company;
use App\Modules\Expenses\Models\Expense;
use App\Modules\Expenses\Repositories\EloquentExpenseRepository;
use App\Modules\Expenses\Repositories\ExpenseRepositoryInterface;
use App\Modules\Expenses\Services\ExpenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ExpenseServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ExpenseService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ExpenseService::class);
    }

    protected function seedAccounts(Company $company): array
    {
        return [
            'maintenance' => Account::factory()->create(['company_id' => $company->id, 'code' => '5000', 'type' => 'expense']),
            'payable' => Account::factory()->create(['company_id' => $company->id, 'code' => '2000', 'type' => 'liability']),
            'cash' => Account::factory()->create(['company_id' => $company->id, 'code' => '1000', 'type' => 'asset']),
            'bank' => Account::factory()->create(['company_id' => $company->id, 'code' => '1100', 'type' => 'asset']),
        ];
    }

    protected function baseAttributes(Company $company, Account $maintenance): array
    {
        return [
            'company_id' => $company->id,
            'account_id' => $maintenance->id,
            'description' => 'Plumbing repair',
            'amount' => 150,
            'expense_date' => now()->toDateString(),
            'payment_status' => 'unpaid',
        ];
    }

    public function test_the_repository_interface_resolves_to_the_eloquent_implementation(): void
    {
        $this->assertInstanceOf(EloquentExpenseRepository::class, app(ExpenseRepositoryInterface::class));
    }

    public function test_paginate_filters_by_search_property_and_payment_status(): void
    {
        $company = Company::factory()->create();
        $account = Account::factory()->create(['company_id' => $company->id, 'type' => 'expense']);
        Expense::factory()->create(['company_id' => $company->id, 'account_id' => $account->id, 'description' => 'Plumbing repair']);
        Expense::factory()->paid()->create(['company_id' => $company->id, 'account_id' => $account->id, 'description' => 'Electricity bill']);

        $this->assertSame(1, $this->service->paginate('Plumbing', null, null)->total());
        $this->assertSame(1, $this->service->paginate(null, null, 'paid')->total());
        $this->assertSame(2, $this->service->paginate(null, null, null)->total());
    }

    public function test_creating_an_unpaid_expense_debits_the_category_and_credits_accounts_payable(): void
    {
        $company = Company::factory()->create();
        $accounts = $this->seedAccounts($company);

        $this->service->create($this->baseAttributes($company, $accounts['maintenance']));

        $this->assertSame(150.0, $accounts['maintenance']->fresh()->balance());
        // Accounts Payable is credit-normal (a liability) - a credited
        // balance shows as positive, representing "$150 owed" the same
        // way an income account shows a positive balance when credited
        // (ARCHITECTURE.md §12's sign convention, already covered by
        // AccountModelTest).
        $this->assertSame(150.0, $accounts['payable']->fresh()->balance());
    }

    public function test_creating_a_paid_expense_credits_cash_by_default(): void
    {
        $company = Company::factory()->create();
        $accounts = $this->seedAccounts($company);

        $this->service->create([
            ...$this->baseAttributes($company, $accounts['maintenance']),
            'payment_status' => 'paid',
            'payment_method' => 'cash',
            'paid_on' => now()->toDateString(),
        ]);

        $this->assertSame(150.0, $accounts['maintenance']->fresh()->balance());
        // Cash is debit-normal (an asset) - a credited balance (money
        // paid out) shows as negative.
        $this->assertSame(-150.0, $accounts['cash']->fresh()->balance());
        $this->assertSame(0.0, $accounts['payable']->fresh()->balance());
    }

    public function test_creating_a_paid_expense_via_bank_transfer_credits_bank_not_cash(): void
    {
        $company = Company::factory()->create();
        $accounts = $this->seedAccounts($company);

        $this->service->create([
            ...$this->baseAttributes($company, $accounts['maintenance']),
            'payment_status' => 'paid',
            'payment_method' => 'bank_transfer',
            'paid_on' => now()->toDateString(),
        ]);

        $this->assertSame(0.0, $accounts['cash']->fresh()->balance());
        $this->assertSame(-150.0, $accounts['bank']->fresh()->balance());
    }

    public function test_creating_an_expense_without_default_accounts_configured_does_not_fail(): void
    {
        $company = Company::factory()->create();
        $maintenance = Account::factory()->create(['company_id' => $company->id, 'type' => 'expense']);

        $expense = $this->service->create($this->baseAttributes($company, $maintenance));

        $this->assertNotNull($expense->id);
        $this->assertSame(0, JournalEntry::query()->where('company_id', $company->id)->count());
    }

    public function test_delete_always_rejects(): void
    {
        $company = Company::factory()->create();
        $accounts = $this->seedAccounts($company);
        $expense = $this->service->create($this->baseAttributes($company, $accounts['maintenance']));

        $this->expectException(ValidationException::class);

        $this->service->delete($expense);
    }

    public function test_mark_as_paid_settles_accounts_payable_and_updates_status(): void
    {
        $company = Company::factory()->create();
        $accounts = $this->seedAccounts($company);
        $expense = $this->service->create($this->baseAttributes($company, $accounts['maintenance']));

        $updated = $this->service->markAsPaid($expense, ['payment_method' => 'cash', 'paid_on' => now()->toDateString()]);

        $this->assertSame('paid', $updated->payment_status);
        $this->assertSame(0.0, $accounts['payable']->fresh()->balance());
        $this->assertSame(-150.0, $accounts['cash']->fresh()->balance());
    }

    public function test_mark_as_paid_rejects_an_already_paid_expense(): void
    {
        $company = Company::factory()->create();
        $accounts = $this->seedAccounts($company);
        $expense = $this->service->create([
            ...$this->baseAttributes($company, $accounts['maintenance']),
            'payment_status' => 'paid',
            'payment_method' => 'cash',
            'paid_on' => now()->toDateString(),
        ]);

        $this->expectException(ValidationException::class);

        $this->service->markAsPaid($expense, ['payment_method' => 'cash', 'paid_on' => now()->toDateString()]);
    }

    public function test_void_reverses_all_journal_entries_and_marks_the_expense_voided(): void
    {
        $company = Company::factory()->create();
        $accounts = $this->seedAccounts($company);
        $expense = $this->service->create($this->baseAttributes($company, $accounts['maintenance']));
        $this->service->markAsPaid($expense, ['payment_method' => 'cash', 'paid_on' => now()->toDateString()]);

        $this->service->void($expense->fresh(), 'Recorded in error');

        $this->assertSame('voided', $expense->fresh()->status);
        $this->assertSame(0.0, $accounts['maintenance']->fresh()->balance());
        $this->assertSame(0.0, $accounts['cash']->fresh()->balance());
        $this->assertSame(0.0, $accounts['payable']->fresh()->balance());
        // Two originals (initial + settlement) + two reversals.
        $this->assertSame(4, JournalEntry::query()->where('company_id', $company->id)->count());
    }

    public function test_void_rejects_an_already_voided_expense(): void
    {
        $company = Company::factory()->create();
        $accounts = $this->seedAccounts($company);
        $expense = $this->service->create($this->baseAttributes($company, $accounts['maintenance']));
        $this->service->void($expense);

        $this->expectException(ValidationException::class);

        $this->service->void($expense->fresh());
    }
}
