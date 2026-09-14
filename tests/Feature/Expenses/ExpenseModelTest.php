<?php

namespace Tests\Feature\Expenses;

use App\Modules\Accounting\Models\Account;
use App\Modules\Companies\Models\Company;
use App\Modules\Expenses\Models\Expense;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ExpenseModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_uuid_is_generated_on_creation(): void
    {
        $company = Company::factory()->create();
        $account = Account::factory()->create(['company_id' => $company->id, 'type' => 'expense']);
        $expense = Expense::factory()->create(['company_id' => $company->id, 'account_id' => $account->id]);

        $this->assertTrue(Str::isUuid($expense->uuid));
    }

    public function test_the_route_key_name_is_uuid(): void
    {
        $this->assertSame('uuid', (new Expense())->getRouteKeyName());
    }

    public function test_is_paid_and_is_voided_reflect_their_respective_columns(): void
    {
        $company = Company::factory()->create();
        $account = Account::factory()->create(['company_id' => $company->id, 'type' => 'expense']);
        $unpaid = Expense::factory()->create(['company_id' => $company->id, 'account_id' => $account->id]);
        $paid = Expense::factory()->paid()->create(['company_id' => $company->id, 'account_id' => $account->id]);
        $voided = Expense::factory()->voided()->create(['company_id' => $company->id, 'account_id' => $account->id]);

        $this->assertFalse($unpaid->isPaid());
        $this->assertTrue($paid->isPaid());
        $this->assertTrue($voided->isVoided());
        $this->assertFalse($unpaid->isVoided());
    }
}
