<?php

namespace Tests\Feature\LandlordPortal;

use App\Modules\Companies\Models\Company;
use App\Modules\Landlords\Models\Landlord;
use App\Modules\LandlordPortal\Services\LandlordPortalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LandlordPortalServiceTest extends TestCase
{
    use RefreshDatabase;

    protected LandlordPortalService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(LandlordPortalService::class);
    }

    public function test_enable_access_sets_a_hashed_password_and_returns_the_plaintext_once(): void
    {
        $company = Company::factory()->create();
        $landlord = Landlord::factory()->create(['company_id' => $company->id, 'email' => 'landlord@example.test']);

        $plainPassword = $this->service->enableAccess($landlord);

        $this->assertNotEmpty($plainPassword);
        $this->assertTrue($landlord->fresh()->hasPortalAccess());
        $this->assertTrue(Hash::check($plainPassword, $landlord->fresh()->password));
    }

    public function test_enable_access_requires_an_email_address(): void
    {
        $company = Company::factory()->create();
        $landlord = Landlord::factory()->create(['company_id' => $company->id, 'email' => null]);

        $this->expectException(ValidationException::class);

        $this->service->enableAccess($landlord);
    }

    public function test_enable_access_rejects_an_email_already_used_by_another_portal_enabled_landlord(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $existing = Landlord::factory()->create(['company_id' => $companyA->id, 'email' => 'shared@example.test']);
        $this->service->enableAccess($existing);

        $other = Landlord::factory()->create(['company_id' => $companyB->id, 'email' => 'shared@example.test']);

        $this->expectException(ValidationException::class);

        $this->service->enableAccess($other);
    }

    public function test_disable_access_clears_the_password(): void
    {
        $company = Company::factory()->create();
        $landlord = Landlord::factory()->create(['company_id' => $company->id, 'email' => 'landlord@example.test']);
        $this->service->enableAccess($landlord);

        $this->service->disableAccess($landlord);

        $this->assertFalse($landlord->fresh()->hasPortalAccess());
    }
}
