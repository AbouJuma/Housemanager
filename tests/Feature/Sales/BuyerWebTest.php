<?php

namespace Tests\Feature\Sales;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use App\Modules\Sales\Models\Buyer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BuyerWebTest extends TestCase
{
    use RefreshDatabase;

    protected function makeCompanyAdmin(Company $company): User
    {
        $admin = User::factory()->create(['company_id' => $company->id]);

        PermissionsTeam::runAs($company->id, function () use ($admin): void {
            $role = Role::findOrCreate('Admin', 'web');
            $role->syncPermissions(['buyers.viewAny', 'buyers.view', 'buyers.create', 'buyers.update', 'buyers.delete']);
            $admin->assignRole($role);
        });

        return $admin;
    }

    public function test_a_company_admin_can_update_a_buyer(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $buyer = Buyer::factory()->create(['company_id' => $company->id, 'status' => 'active']);

        $response = $this->actingAs($admin)->put(route('buyers.update', $buyer), [
            'name' => 'Updated Name',
            'phone' => '0712345678',
            'email' => 'updated@example.com',
            'status' => 'inactive',
        ]);

        $response->assertRedirect(route('buyers.show', $buyer));
        $this->assertSame('Updated Name', $buyer->fresh()->name);
        $this->assertSame('inactive', $buyer->fresh()->status);
    }

    public function test_a_company_admin_cannot_update_another_companys_buyer(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $buyer = Buyer::factory()->create(['company_id' => Company::factory()->create()->id]);

        $this->actingAs($admin)->put(route('buyers.update', $buyer), ['name' => 'X', 'phone' => '1'])
            ->assertForbidden();
    }

    public function test_the_edit_form_preselects_an_inactive_buyers_status(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $buyer = Buyer::factory()->create(['company_id' => $company->id, 'status' => 'inactive']);

        $this->actingAs($admin)->get(route('buyers.edit', $buyer))
            ->assertOk()
            ->assertSee('<option value="inactive" selected>Inactive</option>', false);
    }
}
