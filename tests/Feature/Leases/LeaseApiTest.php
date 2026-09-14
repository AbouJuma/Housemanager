<?php

namespace Tests\Feature\Leases;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use App\Modules\Leases\Models\Lease;
use App\Modules\Properties\Models\Building;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use App\Modules\Tenants\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LeaseApiTest extends TestCase
{
    use RefreshDatabase;

    protected function makeCompanyAdmin(Company $company): User
    {
        $admin = User::factory()->create(['company_id' => $company->id]);

        PermissionsTeam::runAs($company->id, function () use ($admin): void {
            $role = Role::findOrCreate('Admin', 'web');
            $role->syncPermissions(['leases.viewAny', 'leases.view', 'leases.create', 'leases.update', 'leases.delete']);
            $admin->assignRole($role);
        });

        return $admin;
    }

    protected function makeUnit(Company $company, string $status = 'vacant'): Unit
    {
        $property = Property::factory()->create(['company_id' => $company->id]);
        $building = Building::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);

        return Unit::factory()->create(['company_id' => $company->id, 'building_id' => $building->id, 'status' => $status, 'rent_amount' => 500]);
    }

    public function test_a_company_admin_can_list_their_own_leases_via_api(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Sanctum::actingAs($admin);
        $tenant = Tenant::factory()->create(['company_id' => $company->id]);
        Lease::factory()->create(['company_id' => $company->id, 'tenant_id' => $tenant->id, 'unit_id' => $this->makeUnit($company, 'occupied')->id]);

        $this->getJson('/api/v1/leases')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_a_company_admin_can_create_a_lease_via_api(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Sanctum::actingAs($admin);
        $tenant = Tenant::factory()->create(['company_id' => $company->id]);
        $unit = $this->makeUnit($company);

        $response = $this->postJson('/api/v1/leases', [
            'tenant_id' => $tenant->id,
            'unit_id' => $unit->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'rent_amount' => 500,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.tenant.uuid', $tenant->uuid);
        $this->assertSame('occupied', $unit->fresh()->status);
    }

    public function test_a_company_admin_cannot_view_another_companys_lease_via_api(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Sanctum::actingAs($admin);
        $otherTenant = Tenant::factory()->create(['company_id' => $otherCompany->id]);
        $otherUnit = $this->makeUnit($otherCompany, 'occupied');
        $otherLease = Lease::factory()->create(['company_id' => $otherCompany->id, 'tenant_id' => $otherTenant->id, 'unit_id' => $otherUnit->id]);

        $this->getJson("/api/v1/leases/{$otherLease->uuid}")->assertForbidden();
    }

    public function test_a_company_admin_can_delete_their_own_lease_via_api(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Sanctum::actingAs($admin);
        $tenant = Tenant::factory()->create(['company_id' => $company->id]);
        $lease = Lease::factory()->create(['company_id' => $company->id, 'tenant_id' => $tenant->id, 'unit_id' => $this->makeUnit($company, 'occupied')->id]);

        $this->deleteJson("/api/v1/leases/{$lease->uuid}")->assertNoContent();

        $this->assertSoftDeleted('leases', ['id' => $lease->id]);
    }
}
