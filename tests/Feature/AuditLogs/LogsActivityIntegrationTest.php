<?php

namespace Tests\Feature\AuditLogs;

use App\Models\User;
use App\Modules\AuditLogs\Models\ActivityLogEntry;
use App\Modules\Companies\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogsActivityIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_company_logs_an_activity(): void
    {
        $company = Company::factory()->create(['name' => 'Acme Property Group']);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'company',
            'event' => 'created',
            'subject_type' => Company::class,
            'subject_id' => $company->id,
        ]);
    }

    public function test_updating_a_company_only_logs_the_changed_attributes(): void
    {
        $company = Company::factory()->create(['name' => 'Original Name']);

        $company->update(['name' => 'Renamed Co']);

        $log = ActivityLogEntry::where('event', 'updated')
            ->where('subject_id', $company->id)
            ->where('subject_type', Company::class)
            ->firstOrFail();

        $this->assertSame(['name' => 'Renamed Co'], $log->properties->get('attributes'));
        $this->assertSame(['name' => 'Original Name'], $log->properties->get('old'));
    }

    public function test_a_users_password_is_never_captured_in_the_log(): void
    {
        $user = User::factory()->create(['name' => 'Original Name']);

        $user->update(['name' => 'Renamed User', 'password' => 'a-new-password']);

        $log = ActivityLogEntry::where('event', 'updated')
            ->where('subject_id', $user->id)
            ->where('subject_type', User::class)
            ->firstOrFail();

        $this->assertArrayNotHasKey('password', $log->properties->get('attributes', []));
        $this->assertArrayNotHasKey('remember_token', $log->properties->get('attributes', []));
    }

    public function test_saving_with_no_actual_changes_logs_nothing(): void
    {
        $company = Company::factory()->create(['name' => 'Same Name']);
        ActivityLogEntry::query()->delete();

        $company->update(['name' => 'Same Name']);

        $this->assertSame(0, ActivityLogEntry::where('subject_id', $company->id)->count());
    }
}
