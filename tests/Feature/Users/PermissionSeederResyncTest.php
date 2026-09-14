<?php

namespace Tests\Feature\Users;

use App\Core\Tenancy\PermissionsTeam;
use App\Modules\Companies\Services\CompanyService;
use App\Modules\Users\Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PermissionSeederResyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_rerunning_the_seeder_backfills_a_new_permission_onto_existing_admin_roles(): void
    {
        $company = app(CompanyService::class)->create(['name' => 'Acme Property Group']);

        // Simulate a brand-new permission a future module adds, arriving
        // after the company (and its Admin role) already existed.
        Permission::findOrCreate('widgets.manage', 'web');

        $adminBefore = PermissionsTeam::runAs($company->id, fn () => Role::where('name', 'Admin')->firstOrFail());
        $this->assertFalse($adminBefore->permissions->pluck('name')->contains('widgets.manage'));

        (new PermissionSeeder())->run();

        $adminAfter = PermissionsTeam::runAs($company->id, fn () => Role::where('name', 'Admin')->firstOrFail());
        $this->assertTrue($adminAfter->permissions->pluck('name')->contains('widgets.manage'));
    }

    public function test_rerunning_the_seeder_does_not_touch_the_staff_role(): void
    {
        $company = app(CompanyService::class)->create(['name' => 'Acme Property Group']);
        $staffBefore = PermissionsTeam::runAs($company->id, fn () => Role::where('name', 'Staff')->firstOrFail());
        $staffPermissionsBefore = $staffBefore->permissions->pluck('name')->sort()->values()->all();

        (new PermissionSeeder())->run();

        $staffAfter = PermissionsTeam::runAs($company->id, fn () => Role::where('name', 'Staff')->firstOrFail());
        $this->assertSame($staffPermissionsBefore, $staffAfter->permissions->pluck('name')->sort()->values()->all());
    }
}
