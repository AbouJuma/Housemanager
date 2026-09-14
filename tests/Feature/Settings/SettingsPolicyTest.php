<?php

namespace Tests\Feature\Settings;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SettingsPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_saas_owner_can_manage_system_settings_but_not_company_settings(): void
    {
        $owner = User::factory()->create();

        $this->assertTrue($owner->can('settings.view-system'));
        $this->assertTrue($owner->can('settings.update-system'));
        $this->assertFalse($owner->can('settings.view-company'));
        $this->assertFalse($owner->can('settings.update-company'));
    }

    public function test_a_company_user_without_permission_cannot_manage_settings(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        PermissionsTeam::runAs($company->id, function () use ($user): void {
            $this->assertFalse($user->can('settings.view-company'));
            $this->assertFalse($user->can('settings.update-company'));
            $this->assertFalse($user->can('settings.view-system'));
            $this->assertFalse($user->can('settings.update-system'));
        });
    }

    public function test_a_company_user_with_the_admin_role_can_manage_company_settings(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->create(['company_id' => $company->id]);

        PermissionsTeam::runAs($company->id, function () use ($admin): void {
            $role = Role::findOrCreate('Admin', 'web');
            $role->syncPermissions(['settings.view', 'settings.update']);
            $admin->assignRole($role);

            $this->assertTrue($admin->can('settings.view-company'));
            $this->assertTrue($admin->can('settings.update-company'));
            $this->assertFalse($admin->can('settings.view-system'));
        });
    }
}
