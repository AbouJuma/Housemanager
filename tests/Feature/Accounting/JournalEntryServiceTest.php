<?php

namespace Tests\Feature\Accounting;

use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Accounting\Repositories\EloquentJournalEntryRepository;
use App\Modules\Accounting\Repositories\JournalEntryRepositoryInterface;
use App\Modules\Accounting\Services\JournalEntryService;
use App\Modules\Companies\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class JournalEntryServiceTest extends TestCase
{
    use RefreshDatabase;

    protected JournalEntryService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(JournalEntryService::class);
    }

    protected function makeAccounts(Company $company): array
    {
        return [
            Account::factory()->create(['company_id' => $company->id, 'code' => '1000', 'type' => 'asset']),
            Account::factory()->create(['company_id' => $company->id, 'code' => '3000', 'type' => 'equity']),
        ];
    }

    public function test_the_repository_interface_resolves_to_the_eloquent_implementation(): void
    {
        $this->assertInstanceOf(EloquentJournalEntryRepository::class, app(JournalEntryRepositoryInterface::class));
    }

    public function test_post_creates_a_balanced_entry_with_its_lines(): void
    {
        $company = Company::factory()->create();
        [$cash, $equity] = $this->makeAccounts($company);

        $entry = $this->service->post([
            'company_id' => $company->id,
            'entry_date' => now()->toDateString(),
            'description' => 'Opening balance',
            'lines' => [
                ['account_id' => $cash->id, 'debit' => 1000],
                ['account_id' => $equity->id, 'credit' => 1000],
            ],
        ]);

        $this->assertSame('posted', $entry->status);
        $this->assertCount(2, $entry->lines);
        $this->assertSame(1000.0, $cash->fresh()->balance());
    }

    public function test_post_rejects_unbalanced_lines(): void
    {
        $company = Company::factory()->create();
        [$cash, $equity] = $this->makeAccounts($company);

        $this->expectException(ValidationException::class);

        $this->service->post([
            'company_id' => $company->id,
            'entry_date' => now()->toDateString(),
            'description' => 'Unbalanced',
            'lines' => [
                ['account_id' => $cash->id, 'debit' => 1000],
                ['account_id' => $equity->id, 'credit' => 900],
            ],
        ]);
    }

    public function test_post_rejects_a_zero_total(): void
    {
        $company = Company::factory()->create();
        [$cash, $equity] = $this->makeAccounts($company);

        $this->expectException(ValidationException::class);

        $this->service->post([
            'company_id' => $company->id,
            'entry_date' => now()->toDateString(),
            'description' => 'Zero',
            'lines' => [
                ['account_id' => $cash->id, 'debit' => 0],
                ['account_id' => $equity->id, 'credit' => 0],
            ],
        ]);
    }

    public function test_void_posts_a_reversing_entry_and_nets_the_balance_to_zero(): void
    {
        $company = Company::factory()->create();
        [$cash, $equity] = $this->makeAccounts($company);
        $entry = $this->service->post([
            'company_id' => $company->id,
            'entry_date' => now()->toDateString(),
            'description' => 'Opening balance',
            'lines' => [
                ['account_id' => $cash->id, 'debit' => 1000],
                ['account_id' => $equity->id, 'credit' => 1000],
            ],
        ]);

        $this->service->void($entry, 'Recorded in error');

        $this->assertSame('voided', $entry->fresh()->status);
        $this->assertSame(0.0, $cash->fresh()->balance());
        $this->assertSame(2, JournalEntry::query()->count());
    }

    public function test_void_rejects_an_already_voided_entry(): void
    {
        $company = Company::factory()->create();
        [$cash, $equity] = $this->makeAccounts($company);
        $entry = $this->service->post([
            'company_id' => $company->id,
            'entry_date' => now()->toDateString(),
            'description' => 'Opening balance',
            'lines' => [
                ['account_id' => $cash->id, 'debit' => 1000],
                ['account_id' => $equity->id, 'credit' => 1000],
            ],
        ]);
        $this->service->void($entry);

        $this->expectException(ValidationException::class);

        $this->service->void($entry->fresh());
    }
}
