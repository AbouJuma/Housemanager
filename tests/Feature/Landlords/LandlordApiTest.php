<?php

namespace Tests\Feature\Landlords;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use App\Modules\Landlords\Models\Landlord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LandlordApiTest extends TestCase
{
    use RefreshDatabase;

    protected function makeCompanyAdmin(Company $company): User
    {
        $admin = User::factory()->create(['company_id' => $company->id]);

        PermissionsTeam::runAs($company->id, function () use ($admin): void {
            $role = Role::findOrCreate('Admin', 'web');
            $role->syncPermissions(['landlords.viewAny', 'landlords.view', 'landlords.create', 'landlords.update', 'landlords.delete']);
            $admin->assignRole($role);
        });

        return $admin;
    }

    public function test_a_company_admin_can_list_their_own_landlords_via_api(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Sanctum::actingAs($admin);
        Landlord::factory()->count(2)->create(['company_id' => $company->id]);

        $this->getJson('/api/v1/landlords')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_a_company_admin_can_create_a_landlord_via_api(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/landlords', [
            'name' => 'Jane Doe',
            'type' => 'individual',
            'bank_name' => 'CRDB Bank',
            'bank_account_number' => '1234567890',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Jane Doe')
            ->assertJsonPath('data.bank_details.bank_name', 'CRDB Bank')
            ->assertJsonPath('data.bank_details.account_number', '1234567890');
    }

    public function test_a_company_admin_cannot_view_another_companys_landlord_via_api(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Sanctum::actingAs($admin);
        $otherLandlord = Landlord::factory()->create(['company_id' => $otherCompany->id]);

        $this->getJson("/api/v1/landlords/{$otherLandlord->uuid}")->assertForbidden();
    }

    public function test_a_company_admin_can_delete_their_own_landlord_via_api(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Sanctum::actingAs($admin);
        $landlord = Landlord::factory()->create(['company_id' => $company->id]);

        $this->deleteJson("/api/v1/landlords/{$landlord->uuid}")->assertNoContent();

        $this->assertSoftDeleted('landlords', ['id' => $landlord->id]);
    }
}
