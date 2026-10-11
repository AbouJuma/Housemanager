<?php

namespace Tests\Feature\Settings;

use App\Modules\Companies\Models\Company;
use App\Modules\Settings\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SettingsService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(SettingsService::class);
    }

    public function test_an_unset_system_setting_returns_its_catalog_default(): void
    {
        $this->assertSame(14, $this->service->getSystem('default_trial_days'));
        $this->assertSame('USD', $this->service->getSystem('default_currency_code'));
    }

    public function test_setting_a_system_value_persists_and_is_read_back_cast(): void
    {
        $this->service->setSystem('default_trial_days', '30');

        $this->assertSame(30, $this->service->getSystem('default_trial_days'));
    }

    public function test_setting_a_value_twice_updates_rather_than_duplicates(): void
    {
        $this->service->setSystem('default_trial_days', 21);
        $this->service->setSystem('default_trial_days', 28);

        $this->assertSame(28, $this->service->getSystem('default_trial_days'));
        $this->assertDatabaseCount('settings', 1);
    }

    public function test_an_unset_company_setting_returns_its_catalog_default(): void
    {
        $company = Company::factory()->create();

        $this->assertSame('M j, Y', $this->service->getForCompany($company->id, 'date_format'));
    }

    public function test_company_settings_are_isolated_per_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $this->service->setForCompany($companyA->id, 'date_format', 'd/m/Y');

        $this->assertSame('d/m/Y', $this->service->getForCompany($companyA->id, 'date_format'));
        $this->assertSame('M j, Y', $this->service->getForCompany($companyB->id, 'date_format'));
    }

    public function test_getting_or_setting_an_unknown_key_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->getSystem('not_a_real_setting');
    }

    public function test_all_for_company_merges_stored_values_with_catalog_defaults(): void
    {
        $company = Company::factory()->create();
        $this->service->setForCompany($company->id, 'date_format', 'Y-m-d');

        $all = $this->service->allForCompany($company->id);

        $this->assertSame('Y-m-d', $all['date_format']);
        $this->assertSame('landlord', $all['payment_details_source']);
        $this->assertSame('', $all['company_bank_name']);
    }

    public function test_all_system_merges_stored_values_with_catalog_defaults(): void
    {
        $this->service->setSystem('default_trial_days', 7);

        $this->assertSame([
            'default_trial_days' => 7,
            'default_currency_code' => 'USD',
        ], $this->service->allSystem());
    }
}
