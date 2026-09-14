<?php

namespace Tests\Feature\Users;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use App\Modules\Users\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    protected UserService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(UserService::class);
    }

    public function test_create_hashes_the_password(): void
    {
        $user = $this->service->create([
            'name' => 'Jane Admin',
            'email' => 'jane@example.com',
            'password' => 'plain-text-password',
        ]);

        $this->assertTrue(Hash::check('plain-text-password', $user->password));
    }

    public function test_update_leaves_the_password_unchanged_when_none_given(): void
    {
        $user = $this->service->create([
            'name' => 'Jane Admin',
            'email' => 'jane@example.com',
            'password' => 'original-password',
        ]);
        $originalHash = $user->password;

        $updated = $this->service->update($user, ['name' => 'Jane Renamed', 'email' => $user->email]);

        $this->assertSame($originalHash, $updated->password);
        $this->assertSame('Jane Renamed', $updated->name);
    }

    public function test_update_rehashes_the_password_when_given(): void
    {
        $user = $this->service->create([
            'name' => 'Jane Admin',
            'email' => 'jane@example.com',
            'password' => 'original-password',
        ]);

        $updated = $this->service->update($user, [
            'name' => 'Jane Admin',
            'email' => $user->email,
            'password' => 'new-password',
        ]);

        $this->assertTrue(Hash::check('new-password', $updated->password));
    }

    public function test_sync_roles_assigns_within_the_users_own_company(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        PermissionsTeam::runAs($company->id, fn () => Role::findOrCreate('Admin', 'web'));

        $this->service->syncRoles($user, ['Admin']);

        $roles = PermissionsTeam::runAs($company->id, fn () => $user->fresh()->getRoleNames());
        $this->assertEqualsCanonicalizing(['Admin'], $roles->all());
    }

    public function test_sync_roles_restores_the_previous_team_context(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        PermissionsTeam::runAs($company->id, fn () => Role::findOrCreate('Admin', 'web'));

        $registrar = app(\Spatie\Permission\PermissionRegistrar::class);
        $registrar->setPermissionsTeamId(null);

        $this->service->syncRoles($user, ['Admin']);

        $this->assertNull($registrar->getPermissionsTeamId());
    }
}
