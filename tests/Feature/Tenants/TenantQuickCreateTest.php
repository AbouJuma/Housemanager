<?php

namespace Tests\Feature\Tenants;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use App\Modules\Tenants\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TenantQuickCreateTest extends TestCase
{
    use RefreshDatabase;

    protected function makeCompanyAdmin(Company $company): User
    {
        $admin = User::factory()->create(['company_id' => $company->id]);

        PermissionsTeam::runAs($company->id, function () use ($admin): void {
            $role = Role::findOrCreate('Admin', 'web');
            $role->syncPermissions(['tenants.viewAny', 'tenants.view', 'tenants.create', 'leases.create', 'leases.viewAny']);
            $admin->assignRole($role);
        });

        return $admin;
    }

    public function test_the_lease_page_quick_create_modal_creates_a_tenant(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);

        // Exactly what the modal's FormData sends - every field present,
        // untouched ones as empty strings.
        $response = $this->actingAs($admin)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
            ->post(route('tenants.store.ajax'), [
                'name' => 'Quick Tenant', 'type' => 'individual', 'status' => 'active',
                'email' => '', 'phone' => '0712345678', 'secondary_phone' => '', 'date_of_birth' => '',
                'identification_type' => '', 'identification_number' => '', 'address_line1' => '',
                'address_line2' => '', 'city' => '', 'state' => '', 'postal_code' => '', 'country_code' => '',
                'emergency_contact_name' => '', 'emergency_contact_phone' => '', 'notes' => '',
            ]);

        $response->assertOk()->assertJsonPath('success', true)->assertJsonPath('tenant.name', 'Quick Tenant');
        $this->assertSame(1, Tenant::withoutGlobalScopes()->where('name', 'Quick Tenant')->count());
    }
}
