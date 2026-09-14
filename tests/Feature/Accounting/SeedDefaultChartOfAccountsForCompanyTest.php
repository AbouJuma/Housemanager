<?php

namespace Tests\Feature\Accounting;

use App\Modules\Accounting\Models\Account;
use App\Modules\Companies\Events\CompanyCreated;
use App\Modules\Companies\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeedDefaultChartOfAccountsForCompanyTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_the_default_chart_of_accounts_when_a_company_is_created(): void
    {
        $company = Company::factory()->create();

        CompanyCreated::dispatch($company);

        $codes = Account::query()->where('company_id', $company->id)->pluck('code')->all();

        foreach (config('accounting.default_chart') as $expected) {
            $this->assertContains($expected['code'], $codes);
        }
        $this->assertCount(count(config('accounting.default_chart')), $codes);
    }
}
