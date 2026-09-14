<?php

namespace Tests\Feature\Notifications;

use App\Models\User;
use App\Modules\Companies\Models\Company;
use App\Modules\Notifications\Notifications\TrialEndingSoon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrialEndingSoonTest extends TestCase
{
    use RefreshDatabase;

    public function test_via_returns_database_and_company_mail_only(): void
    {
        $company = Company::factory()->create();

        $this->assertSame(['database', 'company-mail'], (new TrialEndingSoon($company))->via(null));
    }

    public function test_to_array_contains_the_data_the_bell_ui_and_idempotency_check_need(): void
    {
        $company = Company::factory()->create(['name' => 'Acme', 'trial_ends_at' => now()->addDays(3)]);

        $data = (new TrialEndingSoon($company))->toArray(null);

        $this->assertSame($company->id, $data['company_id']);
        $this->assertSame($company->trial_ends_at->toDateString(), $data['trial_ends_at']);
        $this->assertStringContainsString('Acme', $data['message']);
        $this->assertSame(route('companies.show', $company), $data['url']);
    }

    public function test_to_company_mail_addresses_the_recipient_by_name(): void
    {
        $company = Company::factory()->create(['name' => 'Acme', 'trial_ends_at' => now()->addDays(3)]);
        $admin = User::factory()->make(['name' => 'Jane']);

        $mail = (new TrialEndingSoon($company))->toCompanyMail($admin);

        $this->assertStringContainsString('Jane', $mail['body']);
        $this->assertStringContainsString('Acme', $mail['body']);
        $this->assertArrayHasKey('subject', $mail);
    }
}
