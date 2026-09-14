<?php

namespace Tests\Feature\VisitorManagement;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use App\Modules\Properties\Models\Property;
use App\Modules\VisitorManagement\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VisitorApiTest extends TestCase
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

    public function test_a_company_admin_can_list_their_own_visitors_via_api(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Sanctum::actingAs($admin);
        $property = Property::factory()->create(['company_id' => $company->id]);
        Visitor::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);

        $this->getJson('/api/v1/visitors')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_a_company_admin_can_check_in_a_visitor_via_api(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Sanctum::actingAs($admin);
        $property = Property::factory()->create(['company_id' => $company->id]);

        $response = $this->postJson('/api/v1/visitors', [
            'property_id' => $property->id,
            'visitor_name' => 'John Delivery',
            'purpose' => 'delivery',
        ]);

        $response->assertCreated()->assertJsonPath('data.visitor_name', 'John Delivery')->assertJsonPath('data.is_checked_in', true);
    }

    public function test_a_company_admin_cannot_view_another_companys_visitor_via_api(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Sanctum::actingAs($admin);
        $otherProperty = Property::factory()->create(['company_id' => $otherCompany->id]);
        $otherVisitor = Visitor::factory()->create(['company_id' => $otherCompany->id, 'property_id' => $otherProperty->id]);

        $this->getJson("/api/v1/visitors/{$otherVisitor->uuid}")->assertForbidden();
    }

    public function test_a_company_admin_can_check_out_a_visitor_via_api(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Sanctum::actingAs($admin);
        $property = Property::factory()->create(['company_id' => $company->id]);
        $visitor = Visitor::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);

        $response = $this->postJson("/api/v1/visitors/{$visitor->uuid}/check-out");

        $response->assertOk()->assertJsonPath('data.is_checked_in', false);
    }

    public function test_a_company_admin_can_delete_their_own_visitor_via_api(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Sanctum::actingAs($admin);
        $property = Property::factory()->create(['company_id' => $company->id]);
        $visitor = Visitor::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);

        $this->deleteJson("/api/v1/visitors/{$visitor->uuid}")->assertNoContent();

        $this->assertSoftDeleted('visitors', ['id' => $visitor->id]);
    }
}
