<?php

namespace Tests\Feature\LandlordPortal;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use App\Modules\Landlords\Models\Landlord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LandlordPortalAccessWebTest extends TestCase
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

    public function test_a_company_admin_can_enable_portal_access_and_sees_the_password_once(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $landlord = Landlord::factory()->create(['company_id' => $company->id, 'email' => 'landlord@example.test']);

        $response = $this->actingAs($admin)->post(route('landlords.portal-access.enable', $landlord));

        $response->assertRedirect(route('landlords.show', $landlord));
        $response->assertSessionHas('portal_password');
        $this->assertTrue($landlord->fresh()->hasPortalAccess());
    }

    public function test_a_company_admin_can_disable_portal_access(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $landlord = Landlord::factory()->create(['company_id' => $company->id, 'email' => 'landlord@example.test']);
        $this->actingAs($admin)->post(route('landlords.portal-access.enable', $landlord));

        $response = $this->actingAs($admin)->post(route('landlords.portal-access.disable', $landlord));

        $response->assertRedirect(route('landlords.show', $landlord));
        $this->assertFalse($landlord->fresh()->hasPortalAccess());
    }

    public function test_a_company_admin_cannot_manage_another_companys_landlord_portal_access(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $otherLandlord = Landlord::factory()->create(['company_id' => $otherCompany->id, 'email' => 'other@example.test']);

        $this->actingAs($admin)->post(route('landlords.portal-access.enable', $otherLandlord))->assertForbidden();
    }
}
