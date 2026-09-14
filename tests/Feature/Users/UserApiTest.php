<?php

namespace Tests\Feature\Users;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_saas_owner_can_list_users_via_api(): void
    {
        $owner = User::factory()->create();
        Sanctum::actingAs($owner);
        $company = Company::factory()->create();
        User::factory()->create(['company_id' => $company->id]);

        $this->getJson('/api/v1/users')->assertOk();
    }

    public function test_a_company_user_without_permission_cannot_list_users_via_api(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/users')->assertForbidden();
    }

    public function test_saas_owner_can_create_a_user_via_api(): void
    {
        $owner = User::factory()->create();
        Sanctum::actingAs($owner);
        $company = Company::factory()->create();

        $response = $this->postJson('/api/v1/users', [
            'name' => 'New User',
            'email' => 'api-user@example.com',
            'password' => 'password123',
            'company_id' => $company->id,
        ]);

        $response->assertCreated()->assertJsonPath('data.email', 'api-user@example.com');
    }

    public function test_a_company_admin_can_view_their_own_companys_user_via_api(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->create(['company_id' => $company->id]);
        PermissionsTeam::runAs($company->id, function () use ($admin): void {
            $role = Role::findOrCreate('Admin', 'web');
            $role->syncPermissions(['users.view']);
            $admin->assignRole($role);
        });
        $target = User::factory()->create(['company_id' => $company->id]);
        Sanctum::actingAs($admin);

        $this->getJson("/api/v1/users/{$target->uuid}")
            ->assertOk()
            ->assertJsonPath('data.uuid', $target->uuid);
    }

    public function test_a_company_admin_cannot_view_another_companys_user_via_api(): void
    {
        $ownCompany = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $admin = User::factory()->create(['company_id' => $ownCompany->id]);
        PermissionsTeam::runAs($ownCompany->id, function () use ($admin): void {
            $role = Role::findOrCreate('Admin', 'web');
            $role->syncPermissions(['users.view']);
            $admin->assignRole($role);
        });
        $target = User::factory()->create(['company_id' => $otherCompany->id]);
        Sanctum::actingAs($admin);

        $this->getJson("/api/v1/users/{$target->uuid}")->assertForbidden();
    }
}
