<?php

namespace Tests\Feature\Accounting;

use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Companies\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AccountModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_uuid_is_generated_on_creation(): void
    {
        $account = Account::factory()->create(['company_id' => Company::factory()->create()->id]);

        $this->assertTrue(Str::isUuid($account->uuid));
    }

    public function test_the_route_key_name_is_uuid(): void
    {
        $this->assertSame('uuid', (new Account())->getRouteKeyName());
    }

    public function test_is_active_defaults_to_true(): void
    {
        $account = Account::factory()->create(['company_id' => Company::factory()->create()->id]);

        $this->assertTrue($account->is_active);
    }

    public function test_balance_is_positive_for_a_debit_normal_account_with_net_debits(): void
    {
        $company = Company::factory()->create();
        $cash = Account::factory()->create(['company_id' => $company->id, 'type' => 'asset']);
        $equity = Account::factory()->create(['company_id' => $company->id, 'type' => 'equity']);
        $entry = JournalEntry::factory()->create(['company_id' => $company->id]);
        $entry->lines()->create(['company_id' => $company->id, 'account_id' => $cash->id, 'debit' => 500, 'credit' => 0]);
        $entry->lines()->create(['company_id' => $company->id, 'account_id' => $equity->id, 'debit' => 0, 'credit' => 500]);

        $this->assertSame(500.0, $cash->balance());
    }

    public function test_balance_is_positive_for_a_credit_normal_account_with_net_credits(): void
    {
        $company = Company::factory()->create();
        $cash = Account::factory()->create(['company_id' => $company->id, 'type' => 'asset']);
        $income = Account::factory()->create(['company_id' => $company->id, 'type' => 'income']);
        $entry = JournalEntry::factory()->create(['company_id' => $company->id]);
        $entry->lines()->create(['company_id' => $company->id, 'account_id' => $cash->id, 'debit' => 500, 'credit' => 0]);
        $entry->lines()->create(['company_id' => $company->id, 'account_id' => $income->id, 'debit' => 0, 'credit' => 500]);

        $this->assertSame(500.0, $income->balance());
    }
}
