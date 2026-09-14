<?php

namespace Tests\Feature\Invoices;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use App\Modules\Invoices\Models\Invoice;
use App\Modules\Leases\Models\Lease;
use App\Modules\Properties\Models\Building;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use App\Modules\Tenants\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InvoicePolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function makeLease(Company $company): Lease
    {
        $tenant = Tenant::factory()->create(['company_id' => $company->id]);
        $property = Property::factory()->create(['company_id' => $company->id]);
        $building = Building::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);
        $unit = Unit::factory()->create(['company_id' => $company->id, 'building_id' => $building->id, 'status' => 'occupied']);

        return Lease::factory()->create(['company_id' => $company->id, 'tenant_id' => $tenant->id, 'unit_id' => $unit->id]);
    }

    protected function makeInvoice(Company $company): Invoice
    {
        return Invoice::factory()->create(['company_id' => $company->id, 'lease_id' => $this->makeLease($company)->id]);
    }

    protected function makeCompanyUser(Company $company, array $permissions): User
    {
        $user = User::factory()->create(['company_id' => $company->id]);

        PermissionsTeam::runAs($company->id, function () use ($user, $permissions): void {
            $role = Role::findOrCreate('Invoice Manager', 'web');
            $role->syncPermissions($permissions);
            $user->assignRole($role);
        });

        return $user;
    }

    public function test_saas_owner_cannot_manage_invoices(): void
    {
        $owner = User::factory()->create();
        $invoice = $this->makeInvoice(Company::factory()->create());

        $this->assertFalse($owner->can('viewAny', Invoice::class));
        $this->assertFalse($owner->can('view', $invoice));
        $this->assertFalse($owner->can('create', Invoice::class));
    }

    public function test_a_company_user_without_permissions_can_do_nothing(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $invoice = $this->makeInvoice($company);

        $this->assertFalse($user->can('viewAny', Invoice::class));
        $this->assertFalse($user->can('view', $invoice));
        $this->assertFalse($user->can('update', $invoice));
        $this->assertFalse($user->can('delete', $invoice));
    }

    public function test_a_company_user_with_permissions_can_manage_their_own_companys_invoices(): void
    {
        $company = Company::factory()->create();
        $user = $this->makeCompanyUser($company, ['invoices.viewAny', 'invoices.view', 'invoices.create', 'invoices.update', 'invoices.delete']);
        $invoice = $this->makeInvoice($company);

        PermissionsTeam::runAs($company->id, function () use ($user, $invoice): void {
            $this->assertTrue($user->can('viewAny', Invoice::class));
            $this->assertTrue($user->can('view', $invoice));
            $this->assertTrue($user->can('create', Invoice::class));
            $this->assertTrue($user->can('update', $invoice));
            $this->assertTrue($user->can('delete', $invoice));
        });
    }

    public function test_permissions_do_not_cross_the_company_boundary(): void
    {
        $ownCompany = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $user = $this->makeCompanyUser($ownCompany, ['invoices.view', 'invoices.update', 'invoices.delete']);
        $otherInvoice = $this->makeInvoice($otherCompany);

        PermissionsTeam::runAs($ownCompany->id, function () use ($user, $otherInvoice): void {
            $this->assertFalse($user->can('view', $otherInvoice));
            $this->assertFalse($user->can('update', $otherInvoice));
            $this->assertFalse($user->can('delete', $otherInvoice));
        });
    }
}
