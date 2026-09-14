<?php

namespace Tests\Feature\Settings;

use App\Modules\Companies\Services\CompanyService;
use App\Modules\Settings\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyServiceSettingsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_company_uses_the_system_default_trial_length(): void
    {
        app(SettingsService::class)->setSystem('default_trial_days', 45);

        $company = app(CompanyService::class)->create(['name' => 'Acme Property Group']);

        $this->assertTrue($company->trial_ends_at->isBetween(now()->addDays(44), now()->addDays(46)));
    }

    public function test_a_new_company_uses_the_system_default_currency_when_none_given(): void
    {
        app(SettingsService::class)->setSystem('default_currency_code', 'TZS');

        $company = app(CompanyService::class)->create(['name' => 'Acme Property Group']);

        $this->assertSame('TZS', $company->currency_code);
    }

    public function test_an_explicit_currency_overrides_the_system_default(): void
    {
        app(SettingsService::class)->setSystem('default_currency_code', 'TZS');

        $company = app(CompanyService::class)->create([
            'name' => 'Acme Property Group',
            'currency_code' => 'KES',
        ]);

        $this->assertSame('KES', $company->currency_code);
    }

    public function test_with_no_system_setting_stored_the_catalog_default_applies(): void
    {
        $company = app(CompanyService::class)->create(['name' => 'Acme Property Group']);

        $this->assertSame('USD', $company->currency_code);
        $this->assertTrue($company->trial_ends_at->isBetween(now()->addDays(13), now()->addDays(15)));
    }
}
