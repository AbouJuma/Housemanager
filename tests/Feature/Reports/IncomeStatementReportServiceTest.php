<?php

namespace Tests\Feature\Reports;

use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Companies\Models\Company;
use App\Modules\Reports\Services\IncomeStatementReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class IncomeStatementReportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_only_includes_journal_entries_within_the_date_range(): void
    {
        $company = Company::factory()->create();
        $cash = Account::factory()->create(['company_id' => $company->id, 'type' => 'asset']);
        $income = Account::factory()->create(['company_id' => $company->id, 'type' => 'income', 'code' => '4000', 'name' => 'Rental Income']);

        $inRange = JournalEntry::factory()->create(['company_id' => $company->id, 'entry_date' => '2026-03-15']);
        $inRange->lines()->create(['company_id' => $company->id, 'account_id' => $cash->id, 'debit' => 500, 'credit' => 0]);
        $inRange->lines()->create(['company_id' => $company->id, 'account_id' => $income->id, 'debit' => 0, 'credit' => 500]);

        $outOfRange = JournalEntry::factory()->create(['company_id' => $company->id, 'entry_date' => '2026-05-01']);
        $outOfRange->lines()->create(['company_id' => $company->id, 'account_id' => $cash->id, 'debit' => 300, 'credit' => 0]);
        $outOfRange->lines()->create(['company_id' => $company->id, 'account_id' => $income->id, 'debit' => 0, 'credit' => 300]);

        $result = app(IncomeStatementReportService::class)->generate(Carbon::parse('2026-03-01'), Carbon::parse('2026-03-31'));

        $this->assertSame(500.0, $result['total_income']);
        $this->assertSame(500.0, $result['income']->firstWhere('code', '4000')['amount']);
    }

    public function test_net_income_is_income_minus_expenses(): void
    {
        $company = Company::factory()->create();
        $cash = Account::factory()->create(['company_id' => $company->id, 'type' => 'asset']);
        $income = Account::factory()->create(['company_id' => $company->id, 'type' => 'income']);
        $expense = Account::factory()->create(['company_id' => $company->id, 'type' => 'expense']);

        $entry1 = JournalEntry::factory()->create(['company_id' => $company->id, 'entry_date' => '2026-03-10']);
        $entry1->lines()->create(['company_id' => $company->id, 'account_id' => $cash->id, 'debit' => 1000, 'credit' => 0]);
        $entry1->lines()->create(['company_id' => $company->id, 'account_id' => $income->id, 'debit' => 0, 'credit' => 1000]);

        $entry2 = JournalEntry::factory()->create(['company_id' => $company->id, 'entry_date' => '2026-03-12']);
        $entry2->lines()->create(['company_id' => $company->id, 'account_id' => $expense->id, 'debit' => 300, 'credit' => 0]);
        $entry2->lines()->create(['company_id' => $company->id, 'account_id' => $cash->id, 'debit' => 0, 'credit' => 300]);

        $result = app(IncomeStatementReportService::class)->generate(Carbon::parse('2026-03-01'), Carbon::parse('2026-03-31'));

        $this->assertSame(1000.0, $result['total_income']);
        $this->assertSame(300.0, $result['total_expenses']);
        $this->assertSame(700.0, $result['net_income']);
    }

    public function test_a_voided_entrys_reversal_nets_to_zero(): void
    {
        $company = Company::factory()->create();
        $cash = Account::factory()->create(['company_id' => $company->id, 'type' => 'asset']);
        $income = Account::factory()->create(['company_id' => $company->id, 'type' => 'income']);

        $original = JournalEntry::factory()->create(['company_id' => $company->id, 'entry_date' => '2026-03-10']);
        $original->lines()->create(['company_id' => $company->id, 'account_id' => $cash->id, 'debit' => 500, 'credit' => 0]);
        $original->lines()->create(['company_id' => $company->id, 'account_id' => $income->id, 'debit' => 0, 'credit' => 500]);

        $reversal = JournalEntry::factory()->create(['company_id' => $company->id, 'entry_date' => '2026-03-11']);
        $reversal->lines()->create(['company_id' => $company->id, 'account_id' => $cash->id, 'debit' => 0, 'credit' => 500]);
        $reversal->lines()->create(['company_id' => $company->id, 'account_id' => $income->id, 'debit' => 500, 'credit' => 0]);

        $result = app(IncomeStatementReportService::class)->generate(Carbon::parse('2026-03-01'), Carbon::parse('2026-03-31'));

        $this->assertSame(0.0, $result['total_income']);
    }
}
