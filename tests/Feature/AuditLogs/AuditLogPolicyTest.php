<?php

namespace Tests\Feature\AuditLogs;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\AuditLogs\Models\ActivityLogEntry;
use App\Modules\Companies\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuditLogPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_saas_owner_can_view_audit_logs(): void
    {
        $owner = User::factory()->create();

        $this->assertTrue($owner->can('viewAny', ActivityLogEntry::class));
    }

    public function test_a_company_user_without_permission_cannot_view_audit_logs(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        PermissionsTeam::runAs($company->id, function () use ($user): void {
            $this->assertFalse($user->can('viewAny', ActivityLogEntry::class));
        });
    }

    public function test_a_company_user_with_permission_can_view_audit_logs(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        PermissionsTeam::runAs($company->id, function () use ($user): void {
            $role = Role::findOrCreate('Admin', 'web');
            $role->syncPermissions(['audit-logs.view']);
            $user->assignRole($role);

            $this->assertTrue($user->can('viewAny', ActivityLogEntry::class));
        });
    }
}
