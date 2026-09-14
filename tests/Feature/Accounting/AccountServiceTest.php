<?php

namespace Tests\Feature\Accounting;

use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Repositories\AccountRepositoryInterface;
use App\Modules\Accounting\Repositories\EloquentAccountRepository;
use App\Modules\Accounting\Services\AccountService;
use App\Modules\Companies\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AccountService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(AccountService::class);
    }

    public function test_the_repository_interface_resolves_to_the_eloquent_implementation(): void
    {
        $this->assertInstanceOf(EloquentAccountRepository::class, app(AccountRepositoryInterface::class));
    }

    public function test_paginate_filters_by_search_and_type(): void
    {
        $company = Company::factory()->create();
        Account::factory()->create(['company_id' => $company->id, 'code' => '1000', 'name' => 'Cash', 'type' => 'asset']);
        Account::factory()->create(['company_id' => $company->id, 'code' => '4000', 'name' => 'Rental Income', 'type' => 'income']);

        $this->assertSame(1, $this->service->paginate('Cash', null)->total());
        $this->assertSame(1, $this->service->paginate(null, 'income')->total());
        $this->assertSame(2, $this->service->paginate(null, null)->total());
    }

    public function test_paginate_includes_the_balance_via_eager_loaded_sums(): void
    {
        $company = Company::factory()->create();
        $cash = Account::factory()->create(['company_id' => $company->id, 'code' => '1000', 'type' => 'asset']);
        $entry = \App\Modules\Accounting\Models\JournalEntry::factory()->create(['company_id' => $company->id]);
        $equity = Account::factory()->create(['company_id' => $company->id, 'code' => '3000', 'type' => 'equity']);
        $entry->lines()->create(['company_id' => $company->id, 'account_id' => $cash->id, 'debit' => 200, 'credit' => 0]);
        $entry->lines()->create(['company_id' => $company->id, 'account_id' => $equity->id, 'debit' => 0, 'credit' => 200]);

        $result = $this->service->paginate(null, null)->firstWhere('id', $cash->id);

        $this->assertSame(200.0, $result->balance());
    }
}
