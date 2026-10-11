<?php

namespace Tests\Feature\TenantPortal;

use App\Modules\Companies\Models\Company;
use App\Modules\Tenants\Models\Tenant;
use App\Modules\TenantPortal\Services\TenantPortalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TenantPortalServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TenantPortalService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(TenantPortalService::class);
    }

    public function test_enable_access_sets_a_hashed_password_and_returns_the_plaintext_once(): void
    {
        $company = Company::factory()->create();
        $tenant = Tenant::factory()->create(['company_id' => $company->id, 'email' => 'tenant@example.test']);

        $plainPassword = $this->service->enableAccess($tenant);

        $this->assertNotEmpty($plainPassword);
        $this->assertTrue($tenant->fresh()->hasPortalAccess());
        $this->assertTrue(Hash::check($plainPassword, $tenant->fresh()->password));
    }

    public function test_enable_access_works_without_an_email_and_starts_with_the_phone_number(): void
    {
        // Username or phone number are enough to sign in - no email needed.
        $company = Company::factory()->create();
        $tenant = Tenant::factory()->create(['company_id' => $company->id, 'email' => null, 'phone' => '0712 345 678']);

        $password = $this->service->enableAccess($tenant);

        $this->assertSame('0712345678', $password);
        $this->assertNotNull(\App\Core\Auth\LoginIdentity::attempt(Tenant::class, $tenant->fresh()->username, '0712345678'));
    }

    public function test_enable_access_rejects_an_email_already_used_by_another_portal_enabled_tenant(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $existing = Tenant::factory()->create(['company_id' => $companyA->id, 'email' => 'shared@example.test']);
        $this->service->enableAccess($existing);

        $other = Tenant::factory()->create(['company_id' => $companyB->id, 'email' => 'shared@example.test']);

        $this->expectException(ValidationException::class);

        $this->service->enableAccess($other);
    }

    public function test_enable_access_does_not_conflict_with_a_non_portal_tenant_sharing_the_same_email(): void
    {
        $company = Company::factory()->create();
        Tenant::factory()->create(['company_id' => $company->id, 'email' => 'shared@example.test']);
        $tenant = Tenant::factory()->create(['company_id' => $company->id, 'email' => 'shared2@example.test']);

        $plainPassword = $this->service->enableAccess($tenant);

        $this->assertNotEmpty($plainPassword);
    }

    public function test_disable_access_clears_the_password(): void
    {
        $company = Company::factory()->create();
        $tenant = Tenant::factory()->create(['company_id' => $company->id, 'email' => 'tenant@example.test']);
        $this->service->enableAccess($tenant);

        $this->service->disableAccess($tenant);

        $this->assertFalse($tenant->fresh()->hasPortalAccess());
    }
}
