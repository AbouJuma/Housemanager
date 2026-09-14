<?php

namespace Tests\Feature\Settings;

use App\Core\Tenancy\CompanyContext;
use App\Modules\Companies\Models\Company;
use App\Modules\Settings\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingHelperTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_falls_back_to_the_catalog_default_with_no_company_in_context(): void
    {
        app(CompanyContext::class)->clear();

        $this->assertSame('M j, Y', setting('date_format'));
    }

    public function test_it_reads_the_current_companys_value(): void
    {
        $company = Company::factory()->create();
        app(SettingsService::class)->setForCompany($company->id, 'date_format', 'd/m/Y');

        app(CompanyContext::class)->set($company->id);

        $this->assertSame('d/m/Y', setting('date_format'));
    }
}
