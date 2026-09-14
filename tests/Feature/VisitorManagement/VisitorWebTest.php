<?php

namespace Tests\Feature\VisitorManagement;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use App\Modules\Properties\Models\Property;
use App\Modules\VisitorManagement\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VisitorWebTest extends TestCase
{
    use RefreshDatabase;

    protected function makeCompanyAdmin(Company $company): User
    {
        $admin = User::factory()->create(['company_id' => $company->id]);

        PermissionsTeam::runAs($company->id, function () use ($admin): void {
            $role = Role::findOrCreate('Admin', 'web');
            $role->syncPermissions(['visitors.viewAny', 'visitors.view', 'visitors.create', 'visitors.update', 'visitors.delete']);
            $admin->assignRole($role);
        });

        return $admin;
    }

    public function test_saas_owner_cannot_access_visitors(): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner)->get(route('visitors.index'))->assertForbidden();
    }

    public function test_a_company_admin_can_check_in_a_visitor(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $property = Property::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($admin)->post(route('visitors.store'), [
            'property_id' => $property->id,
            'visitor_name' => 'John Delivery',
            'purpose' => 'delivery',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('visitors', ['company_id' => $company->id, 'visitor_name' => 'John Delivery', 'checked_in_by_id' => $admin->id]);
        $visitor = Visitor::where('visitor_name', 'John Delivery')->first();
        $this->assertNotNull($visitor->check_in_at);
    }

    public function test_a_company_admin_cannot_view_another_companys_visitor(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $otherProperty = Property::factory()->create(['company_id' => $otherCompany->id]);
        $otherVisitor = Visitor::factory()->create(['company_id' => $otherCompany->id, 'property_id' => $otherProperty->id]);

        $this->actingAs($admin)->get(route('visitors.show', $otherVisitor))->assertForbidden();
    }

    public function test_checking_out_a_visitor_sets_check_out_at(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $property = Property::factory()->create(['company_id' => $company->id]);
        $visitor = Visitor::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);

        $response = $this->actingAs($admin)->post(route('visitors.check-out', $visitor));

        $response->assertRedirect();
        $this->assertNotNull($visitor->fresh()->check_out_at);
        $this->assertSame($admin->id, $visitor->fresh()->checked_out_by_id);
    }

    public function test_checking_out_an_already_checked_out_visitor_fails(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $property = Property::factory()->create(['company_id' => $company->id]);
        $visitor = Visitor::factory()->checkedOut()->create(['company_id' => $company->id, 'property_id' => $property->id]);

        $response = $this->actingAs($admin)->post(route('visitors.check-out', $visitor));

        $response->assertSessionHasErrors('check_out_at');
    }

    public function test_deleting_a_visitor_redirects_to_the_index(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $property = Property::factory()->create(['company_id' => $company->id]);
        $visitor = Visitor::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);

        $response = $this->actingAs($admin)->delete(route('visitors.destroy', $visitor));

        $response->assertRedirect(route('visitors.index'));
        $this->assertSoftDeleted('visitors', ['id' => $visitor->id]);
    }
}
