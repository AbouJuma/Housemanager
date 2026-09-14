<?php

namespace Tests\Feature\Users;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_without_a_company_is_a_saas_owner(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->isSaasOwner());
    }

    public function test_a_user_with_a_company_is_not_a_saas_owner(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $this->assertFalse($user->isSaasOwner());
    }

    public function test_a_uuid_is_generated_on_creation(): void
    {
        $user = User::factory()->create();

        $this->assertTrue(Str::isUuid($user->uuid));
    }

    public function test_role_names_for_own_company_reads_correctly_regardless_of_the_current_team_context(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        PermissionsTeam::runAs($company->id, function () use ($user): void {
            $role = Role::findOrCreate('Admin', 'web');
            $user->assignRole($role);
        });

        // Simulate the acting viewer being in a completely different team
        // context (e.g. the SaaS owner, whose own context is null).
        PermissionsTeam::runAs(null, function () use ($user): void {
            $this->assertEqualsCanonicalizing(['Admin'], $user->roleNamesForOwnCompany()->all());
        });
    }
}
