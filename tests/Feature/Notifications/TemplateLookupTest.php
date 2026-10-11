<?php

namespace Tests\Feature\Notifications;

use App\Core\Tenancy\CompanyContext;
use App\Models\MessageTemplate;
use App\Modules\Companies\Models\Company;
use App\Modules\Invoices\Notifications\TenantPaymentConfirmation;
use App\Modules\Tenants\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression: during a real logged-in request BelongsToCompany's global
 * scope adds `company_id = <company>`, which used to turn the shared
 * template lookup (company_id NULL) into an always-empty query. Edited
 * shared wording was never used, stale per-company copies won instead,
 * and any type without such a copy silently sent nothing.
 */
class TemplateLookupTest extends TestCase
{
    use RefreshDatabase;

    protected function makeTemplate(?int $companyId, string $sms, bool $active = true): MessageTemplate
    {
        return MessageTemplate::withoutGlobalScopes()->create([
            'company_id' => $companyId,
            'type' => 'tenant_payment_notification',
            'name' => 'Tenant Payment',
            'sms_template' => $sms,
            'email_subject' => 'Paid',
            'email_template' => 'Paid {amount}',
            'is_active' => $active,
        ]);
    }

    protected function notification(): TenantPaymentConfirmation
    {
        return new TenantPaymentConfirmation(['amount' => '50,000']);
    }

    public function test_the_shared_template_is_used_inside_a_company_request(): void
    {
        $company = Company::factory()->create();
        $tenant = Tenant::factory()->create(['company_id' => $company->id]);
        $this->makeTemplate(null, 'SHARED {amount}');

        app(CompanyContext::class)->set($company->id);

        $this->assertSame('SHARED 50,000', $this->notification()->toCompanySms($tenant));
    }

    public function test_the_shared_template_wins_over_a_stale_company_copy(): void
    {
        $company = Company::factory()->create();
        $tenant = Tenant::factory()->create(['company_id' => $company->id]);
        $this->makeTemplate(null, 'SHARED {amount}');
        $this->makeTemplate($company->id, 'OLD COPY {amount}');

        app(CompanyContext::class)->set($company->id);

        $this->assertSame('SHARED 50,000', $this->notification()->toCompanySms($tenant));
    }

    public function test_a_company_template_is_the_fallback_when_there_is_no_shared_one(): void
    {
        $company = Company::factory()->create();
        $tenant = Tenant::factory()->create(['company_id' => $company->id]);
        $this->makeTemplate($company->id, 'COMPANY {amount}');

        app(CompanyContext::class)->set($company->id);

        $this->assertSame('COMPANY 50,000', $this->notification()->toCompanySms($tenant));
    }

    public function test_an_inactive_shared_template_sends_nothing(): void
    {
        $company = Company::factory()->create();
        $tenant = Tenant::factory()->create(['company_id' => $company->id]);
        $this->makeTemplate(null, 'SHARED {amount}', active: false);

        app(CompanyContext::class)->set($company->id);

        $this->assertSame('', $this->notification()->toCompanySms($tenant));
    }
}
