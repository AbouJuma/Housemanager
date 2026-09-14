<?php

namespace Tests\Feature\Accounting;

use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Companies\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class JournalEntryModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_uuid_is_generated_on_creation(): void
    {
        $entry = JournalEntry::factory()->create(['company_id' => Company::factory()->create()->id]);

        $this->assertTrue(Str::isUuid($entry->uuid));
    }

    public function test_the_route_key_name_is_uuid(): void
    {
        $this->assertSame('uuid', (new JournalEntry())->getRouteKeyName());
    }

    public function test_is_voided_reflects_status(): void
    {
        $posted = JournalEntry::factory()->create(['company_id' => Company::factory()->create()->id]);
        $voided = JournalEntry::factory()->voided()->create(['company_id' => Company::factory()->create()->id]);

        $this->assertFalse($posted->isVoided());
        $this->assertTrue($voided->isVoided());
    }

    public function test_total_debits_sums_its_lines(): void
    {
        $company = Company::factory()->create();
        $cash = Account::factory()->create(['company_id' => $company->id]);
        $equity = Account::factory()->create(['company_id' => $company->id]);
        $entry = JournalEntry::factory()->create(['company_id' => $company->id]);
        $entry->lines()->create(['company_id' => $company->id, 'account_id' => $cash->id, 'debit' => 300, 'credit' => 0]);
        $entry->lines()->create(['company_id' => $company->id, 'account_id' => $equity->id, 'debit' => 0, 'credit' => 300]);

        $this->assertSame(300.0, $entry->fresh('lines')->totalDebits());
    }
}
