<?php

namespace Tests\Feature\Dashboard;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SearchControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_short_query_returns_no_groups(): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner)->getJson('/search?q=a')
            ->assertOk()
            ->assertJson(['groups' => []]);
    }

    public function test_saas_owner_can_search_companies_and_users_across_companies(): void
    {
        $owner = User::factory()->create();
        $company = Company::factory()->create(['name' => 'Acme Property Group']);
        $user = User::factory()->create(['name' => 'Jane Findable', 'company_id' => $company->id]);

        $response = $this->actingAs($owner)->getJson('/search?q=Acme');
        $response->assertOk();
        $companyGroup = collect($response->json('groups'))->firstWhere('label', 'Companies');
        $this->assertNotNull($companyGroup);
        $this->assertSame('Acme Property Group', $companyGroup['items'][0]['title']);

        $response = $this->actingAs($owner)->getJson('/search?q=Jane');
        $userGroup = collect($response->json('groups'))->firstWhere('label', 'Users');
        $this->assertSame('Jane Findable', $userGroup['items'][0]['title']);
    }

    public function test_a_company_user_without_permission_gets_no_groups(): void
    {
        $company = Company::factory()->create(['name' => 'Findable Co']);
        $user = User::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->getJson('/search?q=Findable');

        $response->assertOk()->assertJson(['groups' => []]);
    }

    public function test_a_company_admin_can_only_find_users_in_their_own_company(): void
    {
        $ownCompany = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $admin = User::factory()->create(['company_id' => $ownCompany->id]);
        User::factory()->create(['name' => 'Own Company Match', 'company_id' => $ownCompany->id]);
        User::factory()->create(['name' => 'Other Company Match', 'company_id' => $otherCompany->id]);

        PermissionsTeam::runAs($ownCompany->id, function () use ($admin): void {
            $role = Role::findOrCreate('Admin', 'web');
            $role->syncPermissions(['users.viewAny']);
            $admin->assignRole($role);
        });

        $response = $this->actingAs($admin)->getJson('/search?q=Match');

        $response->assertOk();
        $userGroup = collect($response->json('groups'))->firstWhere('label', 'Users');
        $titles = collect($userGroup['items'])->pluck('title')->all();

        $this->assertContains('Own Company Match', $titles);
        $this->assertNotContains('Other Company Match', $titles);

        // A company admin never gets a Companies group at all, even
        // matching their own company's name.
        $this->assertNull(collect($response->json('groups'))->firstWhere('label', 'Companies'));
    }
}
