<?php

namespace Tests\Feature\Reports;

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

/**
 * JSON mirror (mobile staff area) of App\Modules\Reports\Http\
 * Controllers\ReportController's rentRoll()/occupancy() views - same
 * services, same reports.view permission gate.
 */
class ReportApiTest extends TestCase
{
    use RefreshDatabase;

    protected function makeToken(Company $company, array $permissions): string
    {
        $admin = User::factory()->create(['company_id' => $company->id]);

        PermissionsTeam::runAs($company->id, function () use ($admin, $permissions): void {
            $role = Role::findOrCreate('Admin', 'web');
            $role->syncPermissions($permissions);
            $admin->assignRole($role);
        });

        return $admin->createToken('test')->plainTextToken;
    }

    public function test_rent_roll_lists_every_unit_with_its_tenant_and_rent(): void
    {
        $company = Company::factory()->create();
        $token = $this->makeToken($company, ['reports.view']);
        $property = Property::factory()->create(['company_id' => $company->id, 'name' => 'Palm Court']);
        $building = Building::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);
        $unit = Unit::factory()->create(['company_id' => $company->id, 'building_id' => $building->id, 'unit_number' => 'A1', 'status' => 'occupied']);
        $tenant = Tenant::factory()->create(['company_id' => $company->id, 'name' => 'Amina Tenant']);
        Lease::factory()->create(['company_id' => $company->id, 'tenant_id' => $tenant->id, 'unit_id' => $unit->id, 'status' => 'active', 'rent_amount' => 500]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/reports/rent-roll');

        $response->assertOk();
        $row = collect($response->json('data'))->firstWhere('unit', 'A1');
        $this->assertSame('Palm Court', $row['property']);
        $this->assertSame('Amina Tenant', $row['tenant']);
        $this->assertEquals(500, $row['rent_amount']);
    }

    public function test_occupancy_summarizes_occupied_and_vacant_units_per_property(): void
    {
        $company = Company::factory()->create();
        $token = $this->makeToken($company, ['reports.view']);
        $property = Property::factory()->create(['company_id' => $company->id, 'name' => 'Palm Court']);
        $building = Building::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);
        Unit::factory()->create(['company_id' => $company->id, 'building_id' => $building->id, 'status' => 'occupied']);
        Unit::factory()->create(['company_id' => $company->id, 'building_id' => $building->id, 'status' => 'vacant']);

        $response = $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/reports/occupancy');

        $response->assertOk();
        $row = collect($response->json('data'))->firstWhere('property', 'Palm Court');
        $this->assertSame(2, $row['total_units']);
        $this->assertSame(1, $row['occupied']);
        $this->assertSame(1, $row['vacant']);
        $this->assertEquals(50.0, $row['occupancy_rate']);
    }

    public function test_a_user_without_the_permission_is_forbidden(): void
    {
        $company = Company::factory()->create();
        $token = $this->makeToken($company, []);

        $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/reports/rent-roll')->assertForbidden();
        $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/reports/occupancy')->assertForbidden();
    }
}
