<?php

namespace Tests\Feature\Landlords;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use App\Modules\Landlords\Models\Landlord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LandlordWebTest extends TestCase
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

    public function test_saas_owner_cannot_access_landlords(): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner)->get(route('landlords.index'))->assertForbidden();
    }

    public function test_a_company_admin_can_list_their_own_landlords(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Landlord::factory()->count(2)->create(['company_id' => $company->id]);

        $response = $this->actingAs($admin)->get(route('landlords.index'));

        $response->assertOk();
        $response->assertViewHas('landlords', fn ($landlords) => $landlords->total() === 2);
    }

    public function test_a_company_admin_can_create_a_landlord_with_bank_details(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);

        $response = $this->actingAs($admin)->post(route('landlords.store'), [
            'name' => 'Jane Doe',
            'type' => 'individual',
            'bank_name' => 'CRDB Bank',
            'bank_account_number' => '1234567890',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('landlords', ['name' => 'Jane Doe', 'company_id' => $company->id]);
        $landlord = Landlord::where('name', 'Jane Doe')->first();
        $this->assertSame('CRDB Bank', $landlord->bankDetail('name'));
    }

    public function test_a_company_admin_cannot_view_another_companys_landlord(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $otherLandlord = Landlord::factory()->create(['company_id' => $otherCompany->id]);

        $this->actingAs($admin)->get(route('landlords.show', $otherLandlord))->assertForbidden();
    }

    public function test_a_company_admin_cannot_assign_another_companys_landlord_to_a_property(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        PermissionsTeam::runAs($company->id, function () use ($admin): void {
            $admin->givePermissionTo(['properties.create']);
        });
        $otherLandlord = Landlord::factory()->create(['company_id' => $otherCompany->id]);

        $response = $this->actingAs($admin)->post(route('properties.store'), [
            'name' => 'Sunset Apartments',
            'type' => 'residential',
            'landlord_id' => $otherLandlord->id,
        ]);

        $response->assertSessionHasErrors('landlord_id');
    }

    public function test_deleting_a_landlord_redirects_to_the_index(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $landlord = Landlord::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($admin)->delete(route('landlords.destroy', $landlord));

        $response->assertRedirect(route('landlords.index'));
        $this->assertSoftDeleted('landlords', ['id' => $landlord->id]);
    }
}
