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
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LeaseWebTest extends TestCase
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

    public function test_saas_owner_cannot_access_leases(): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner)->get(route('leases.index'))->assertForbidden();
    }

    public function test_a_company_admin_can_list_their_own_leases(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $tenant = Tenant::factory()->create(['company_id' => $company->id]);
        collect([1, 2])->each(fn () => Lease::factory()->create([
            'company_id' => $company->id,
            'tenant_id' => $tenant->id,
            'unit_id' => $this->makeUnit($company, 'occupied')->id,
        ]));

        $response = $this->actingAs($admin)->get(route('leases.index'));

        $response->assertOk();
        $response->assertViewHas('leases', fn ($leases) => $leases->total() === 2);
    }

    public function test_a_company_admin_can_create_a_lease_for_a_vacant_unit_and_it_becomes_occupied(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $tenant = Tenant::factory()->create(['company_id' => $company->id]);
        $unit = $this->makeUnit($company);

        $response = $this->actingAs($admin)->post(route('leases.store'), [
            'tenant_id' => $tenant->id,
            'unit_id' => $unit->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'billing_cycle' => 'monthly',
            'rent_amount' => 500,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('leases', ['tenant_id' => $tenant->id, 'unit_id' => $unit->id, 'status' => 'active']);
        $this->assertSame('occupied', $unit->fresh()->status);
    }

    public function test_creating_a_lease_for_an_already_occupied_unit_fails_validation(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $tenant = Tenant::factory()->create(['company_id' => $company->id]);
        $unit = $this->makeUnit($company, 'occupied');

        $response = $this->actingAs($admin)->post(route('leases.store'), [
            'tenant_id' => $tenant->id,
            'unit_id' => $unit->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'rent_amount' => 500,
        ]);

        $response->assertSessionHasErrors('unit_id');
    }

    public function test_creating_a_lease_for_a_blacklisted_tenant_fails_validation(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $tenant = Tenant::factory()->blacklisted()->create(['company_id' => $company->id]);
        $unit = $this->makeUnit($company);

        $response = $this->actingAs($admin)->post(route('leases.store'), [
            'tenant_id' => $tenant->id,
            'unit_id' => $unit->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'rent_amount' => 500,
        ]);

        $response->assertSessionHasErrors('tenant_id');
    }

    public function test_terminating_a_lease_frees_the_unit(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $tenant = Tenant::factory()->create(['company_id' => $company->id]);
        $unit = $this->makeUnit($company, 'occupied');
        $lease = Lease::factory()->create(['company_id' => $company->id, 'tenant_id' => $tenant->id, 'unit_id' => $unit->id]);

        $response = $this->actingAs($admin)->put(route('leases.update', $lease), [
            'end_date' => $lease->end_date->toDateString(),
            'billing_cycle' => $lease->billing_cycle,
            'rent_amount' => $lease->rent_amount,
            'status' => 'terminated',
            'termination_reason' => 'Tenant moved out.',
        ]);

        $response->assertRedirect();
        $this->assertSame('terminated', $lease->fresh()->status);
        $this->assertSame('vacant', $unit->fresh()->status);
    }

    public function test_a_company_admin_cannot_view_another_companys_lease(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $otherTenant = Tenant::factory()->create(['company_id' => $otherCompany->id]);
        $otherUnit = $this->makeUnit($otherCompany, 'occupied');
        $otherLease = Lease::factory()->create(['company_id' => $otherCompany->id, 'tenant_id' => $otherTenant->id, 'unit_id' => $otherUnit->id]);

        $this->actingAs($admin)->get(route('leases.show', $otherLease))->assertForbidden();
    }

    public function test_deleting_a_lease_redirects_to_the_index(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $tenant = Tenant::factory()->create(['company_id' => $company->id]);
        $unit = $this->makeUnit($company, 'occupied');
        $lease = Lease::factory()->create(['company_id' => $company->id, 'tenant_id' => $tenant->id, 'unit_id' => $unit->id]);

        $response = $this->actingAs($admin)->delete(route('leases.destroy', $lease));

        $response->assertRedirect(route('leases.index'));
        $this->assertSoftDeleted('leases', ['id' => $lease->id]);
    }
    public function test_the_create_page_exposes_each_vacant_units_rent_for_prefilling(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $unit = $this->makeUnit($company);

        $response = $this->actingAs($admin)->get(route("leases.create"));

        $response->assertOk();
        $response->assertSee("value=\"{$unit->id}\" data-rent-amount=\"500.00\"", false);
    }
}