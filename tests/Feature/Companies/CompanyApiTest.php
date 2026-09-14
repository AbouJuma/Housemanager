<?php

namespace Tests\Feature\Companies;

use App\Models\User;
use App\Modules\Companies\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CompanyApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_saas_owner_can_list_companies_via_api(): void
    {
        $owner = User::factory()->create();
        Sanctum::actingAs($owner);
        Company::factory()->count(2)->create();

        $this->getJson('/api/v1/companies')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_a_company_user_cannot_list_all_companies_via_api(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create();
        $user->company_id = $company->id;
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/companies')->assertForbidden();
    }

    public function test_saas_owner_can_create_a_company_via_api(): void
    {
        $owner = User::factory()->create();
        Sanctum::actingAs($owner);

        $response = $this->postJson('/api/v1/companies', ['name' => 'Acme Property Group']);

        $response->assertCreated()->assertJsonPath('data.name', 'Acme Property Group');
        $this->assertDatabaseHas('companies', ['name' => 'Acme Property Group']);
    }

    public function test_a_company_user_can_view_their_own_company_via_api(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create();
        $user->company_id = $company->id;
        Sanctum::actingAs($user);

        $this->getJson("/api/v1/companies/{$company->uuid}")
            ->assertOk()
            ->assertJsonPath('data.uuid', $company->uuid);
    }

    public function test_a_company_user_cannot_view_another_companys_record_via_api(): void
    {
        $ownCompany = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $user = User::factory()->create();
        $user->company_id = $ownCompany->id;
        Sanctum::actingAs($user);

        $this->getJson("/api/v1/companies/{$otherCompany->uuid}")->assertForbidden();
    }

    public function test_only_the_saas_owner_can_delete_a_company_via_api(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->create();
        Sanctum::actingAs($owner);

        $this->deleteJson("/api/v1/companies/{$company->uuid}")->assertNoContent();

        $this->assertSoftDeleted('companies', ['id' => $company->id]);
    }
}
