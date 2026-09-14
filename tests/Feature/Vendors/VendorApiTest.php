<?php

namespace Tests\Feature\Vendors;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use App\Modules\Vendors\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VendorApiTest extends TestCase
{
    use RefreshDatabase;

    protected function makeCompanyAdmin(Company $company): User
    {
        $admin = User::factory()->create(['company_id' => $company->id]);

        PermissionsTeam::runAs($company->id, function () use ($admin): void {
            $role = Role::findOrCreate('Admin', 'web');
            $role->syncPermissions(['vendors.viewAny', 'vendors.view', 'vendors.create', 'vendors.update', 'vendors.delete']);
            $admin->assignRole($role);
        });

        return $admin;
    }

    public function test_a_company_admin_can_list_their_own_vendors_via_api(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Sanctum::actingAs($admin);
        Vendor::factory()->count(2)->create(['company_id' => $company->id]);

        $this->getJson('/api/v1/vendors')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_a_company_admin_can_create_a_vendor_via_api(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/vendors', [
            'name' => 'Ace Plumbing',
            'type' => 'company',
            'category' => 'plumbing',
        ]);

        $response->assertCreated()->assertJsonPath('data.name', 'Ace Plumbing');
    }

    public function test_a_company_admin_cannot_view_another_companys_vendor_via_api(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Sanctum::actingAs($admin);
        $otherVendor = Vendor::factory()->create(['company_id' => $otherCompany->id]);

        $this->getJson("/api/v1/vendors/{$otherVendor->uuid}")->assertForbidden();
    }

    public function test_a_company_admin_can_delete_their_own_vendor_via_api(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Sanctum::actingAs($admin);
        $vendor = Vendor::factory()->create(['company_id' => $company->id]);

        $this->deleteJson("/api/v1/vendors/{$vendor->uuid}")->assertNoContent();

        $this->assertSoftDeleted('vendors', ['id' => $vendor->id]);
    }
}
