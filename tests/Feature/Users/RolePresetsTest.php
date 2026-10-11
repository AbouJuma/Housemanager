<?php

namespace Tests\Feature\Users;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use App\Modules\Maintenance\Models\MaintenanceRequest;
use App\Modules\Properties\Models\Property;
use App\Modules\Users\Support\RolePresets;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * New staff roles (accountant, caretaker, security...) work in the app
 * through permissions alone: the role-page presets only use real
 * permissions, sign-in hands the app exactly those, and the API lets
 * the role do its job and nothing more.
 */
class RolePresetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_preset_uses_only_real_permissions(): void
    {
        $known = collect(config('permissions'))->flatMap(fn (array $group) => array_keys($group))->all();

        foreach (RolePresets::all() as $key => $preset) {
            $this->assertNotEmpty($preset['permissions'], $key);
            $this->assertSame([], array_values(array_diff($preset['permissions'], $known)), "{$key} names permissions that don't exist");
        }
    }

    public function test_the_new_role_page_offers_the_presets(): void
    {
        $company = Company::factory()->create();
        $admin = $this->staff($company, ['roles.viewAny', 'roles.create'], 'Admin');

        $this->actingAs($admin)->get(route('roles.create'))->assertOk()
            ->assertSee('data-role-preset="accountant"', false)
            ->assertSee('data-role-preset="security"', false)
            ->assertSee('Caretaker / Cleaner');
    }

    public function test_a_caretaker_signs_in_to_the_app_and_can_only_do_maintenance(): void
    {
        $company = Company::factory()->create();
        $caretaker = $this->staff($company, RolePresets::available()['caretaker']['permissions'], 'Caretaker');
        $caretaker->forceFill(['username' => 'care.taker', 'password' => 'Care#2026'])->save();
        $property = Property::factory()->create(['company_id' => $company->id]);
        $job = MaintenanceRequest::factory()->create(['company_id' => $company->id, 'property_id' => $property->id, 'status' => 'open']);

        $login = $this->postJson('/api/v1/auth/login', ['email' => 'care.taker', 'password' => 'Care#2026'])->assertOk();
        $this->assertEqualsCanonicalizing(RolePresets::available()['caretaker']['permissions'], $login->json('permissions'));
        $token = $login->json('token');

        $this->withToken($token)->getJson('/api/v1/maintenance-requests')->assertOk()->assertJsonPath('data.0.uuid', $job->uuid);
        $this->withToken($token)->patchJson("/api/v1/maintenance-requests/{$job->uuid}/status", ['status' => 'in_progress'])
            ->assertOk()->assertJsonPath('data.status', 'in_progress');

        $this->withToken($token)->getJson('/api/v1/invoices')->assertForbidden();
        $this->withToken($token)->getJson('/api/v1/tenants')->assertForbidden();
        $this->withToken($token)->getJson('/api/v1/expenses')->assertForbidden();
    }

    public function test_updating_a_job_status_needs_update_rights(): void
    {
        $company = Company::factory()->create();
        $viewer = $this->staff($company, ['maintenance.viewAny', 'maintenance.view'], 'Viewer');
        $viewer->forceFill(['username' => 'just.looking', 'password' => 'Look#2026'])->save();
        $property = Property::factory()->create(['company_id' => $company->id]);
        $job = MaintenanceRequest::factory()->create(['company_id' => $company->id, 'property_id' => $property->id, 'status' => 'open']);

        $token = $this->postJson('/api/v1/auth/login', ['email' => 'just.looking', 'password' => 'Look#2026'])->json('token');

        $this->withToken($token)->patchJson("/api/v1/maintenance-requests/{$job->uuid}/status", ['status' => 'completed'])->assertForbidden();
        $this->assertSame('open', $job->fresh()->status);
    }

    protected function staff(Company $company, array $permissions, string $role): User
    {
        return PermissionsTeam::runAs($company->id, function () use ($company, $permissions, $role) {
            Role::findOrCreate($role, 'web')->syncPermissions($permissions);
            $user = User::factory()->create(['company_id' => $company->id]);
            $user->assignRole($role);

            return $user;
        });
    }
}
