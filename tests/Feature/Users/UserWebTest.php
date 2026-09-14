<?php

namespace Tests\Feature\Users;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_saas_owner_can_list_users_across_companies(): void
    {
        $owner = User::factory()->create();
        $company = Company::factory()->create();
        User::factory()->create(['company_id' => $company->id]);

        $this->actingAs($owner)->get(route('users.index'))->assertOk();
    }

    public function test_a_company_admin_can_list_only_their_own_companys_users(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        User::factory()->create(['company_id' => $company->id]);

        $this->actingAs($admin)->get(route('users.index'))->assertOk();
    }

    public function test_a_company_user_without_permission_cannot_list_users(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user)->get(route('users.index'))->assertForbidden();
    }

    public function test_saas_owner_can_create_a_user_for_a_company(): void
    {
        $owner = User::factory()->create();
        $company = Company::factory()->create();

        $response = $this->actingAs($owner)->post(route('users.store'), [
            'name' => 'New User',
            'email' => 'new-user@example.com',
            'password' => 'password123',
            'company_id' => $company->id,
        ]);

        $this->assertDatabaseHas('users', ['email' => 'new-user@example.com', 'company_id' => $company->id]);
        $user = User::where('email', 'new-user@example.com')->firstOrFail();
        $response->assertRedirect(route('users.show', $user));
    }

    public function test_a_company_admin_creating_a_user_is_forced_into_their_own_company(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);

        // company_id is a `prohibited` field for non-SaaS-owners; sending
        // one anyway must not let them plant a user in another company.
        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'New User',
            'email' => 'new-user@example.com',
            'password' => 'password123',
            'company_id' => $otherCompany->id,
        ])->assertSessionHasErrors('company_id');
    }

    public function test_a_company_admin_can_assign_roles_when_creating_a_user(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        PermissionsTeam::runAs($company->id, fn () => Role::findOrCreate('Staff', 'web'));

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Created User',
            'email' => 'created-user@example.com',
            'password' => 'password123',
            'roles' => ['Staff'],
        ]);

        $response->assertRedirect();
        $user = User::where('email', 'created-user@example.com')->firstOrFail();
        $roles = PermissionsTeam::runAs($company->id, fn () => $user->fresh()->getRoleNames());
        $this->assertEqualsCanonicalizing(['Staff'], $roles->all());
    }

    public function test_a_company_admin_can_assign_roles_when_editing_a_user(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $target = User::factory()->create(['company_id' => $company->id]);
        PermissionsTeam::runAs($company->id, fn () => Role::findOrCreate('Staff', 'web'));

        $response = $this->actingAs($admin)->put(route('users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'roles' => ['Staff'],
        ]);

        $response->assertRedirect(route('users.show', $target));
        $roles = PermissionsTeam::runAs($company->id, fn () => $target->fresh()->getRoleNames());
        $this->assertEqualsCanonicalizing(['Staff'], $roles->all());
    }

    public function test_a_user_cannot_delete_their_own_account_through_the_admin_ui(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);

        $this->actingAs($admin)->delete(route('users.destroy', $admin))->assertForbidden();
        $this->assertNotNull($admin->fresh());
    }

    protected function makeCompanyAdmin(Company $company): User
    {
        $admin = User::factory()->create(['company_id' => $company->id]);

        PermissionsTeam::runAs($company->id, function () use ($admin): void {
            $role = Role::findOrCreate('Admin', 'web');
            $role->syncPermissions([
                'users.viewAny', 'users.view', 'users.create', 'users.update', 'users.delete',
            ]);
            $admin->assignRole($role);
        });

        return $admin;
    }
}
