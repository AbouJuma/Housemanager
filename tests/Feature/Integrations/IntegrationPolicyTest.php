<?php

namespace Tests\Feature\Integrations;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use App\Modules\Integrations\Models\Integration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class IntegrationPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_saas_owner_cannot_manage_integrations(): void
    {
        $owner = User::factory()->create();
        $company = Company::factory()->create();
        $integration = Integration::create(['company_id' => $company->id, 'type' => 'payment', 'provider' => 'stripe']);

        $this->assertFalse($owner->can('viewAny', Integration::class));
        $this->assertFalse($owner->can('view', $integration));
        $this->assertFalse($owner->can('update', $integration));
    }

    public function test_a_company_user_without_permission_cannot_manage_integrations(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $integration = Integration::create(['company_id' => $company->id, 'type' => 'payment', 'provider' => 'stripe']);

        PermissionsTeam::runAs($company->id, function () use ($user, $integration): void {
            $this->assertFalse($user->can('viewAny', Integration::class));
            $this->assertFalse($user->can('update', $integration));
        });
    }

    public function test_a_company_user_with_permission_can_manage_their_own_companys_integrations(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $integration = Integration::create(['company_id' => $company->id, 'type' => 'payment', 'provider' => 'stripe']);

        PermissionsTeam::runAs($company->id, function () use ($user, $integration): void {
            $role = Role::findOrCreate('Admin', 'web');
            $role->syncPermissions(['integrations.view', 'integrations.update']);
            $user->assignRole($role);

            $this->assertTrue($user->can('viewAny', Integration::class));
            $this->assertTrue($user->can('view', $integration));
            $this->assertTrue($user->can('update', $integration));
        });
    }

    public function test_permissions_do_not_cross_the_company_boundary(): void
    {
        $ownCompany = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $ownCompany->id]);
        $otherIntegration = Integration::create(['company_id' => $otherCompany->id, 'type' => 'payment', 'provider' => 'stripe']);

        PermissionsTeam::runAs($ownCompany->id, function () use ($user, $otherIntegration): void {
            $role = Role::findOrCreate('Admin', 'web');
            $role->syncPermissions(['integrations.view', 'integrations.update']);
            $user->assignRole($role);

            $this->assertFalse($user->can('view', $otherIntegration));
            $this->assertFalse($user->can('update', $otherIntegration));
        });
    }
}
