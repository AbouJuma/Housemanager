<?php

namespace Tests\Feature\Notifications;

use App\Models\User;
use App\Modules\Companies\Models\Company;
use App\Modules\Notifications\Notifications\TrialEndingSoon;
use App\Core\Tenancy\PermissionsTeam;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NotifyTrialsEndingSoonTest extends TestCase
{
    use RefreshDatabase;

    protected function makeCompanyAdmin(Company $company): User
    {
        $admin = User::factory()->create(['company_id' => $company->id]);

        PermissionsTeam::runAs($company->id, function () use ($admin): void {
            $admin->assignRole(Role::findOrCreate('Admin', 'web'));
        });

        return $admin;
    }

    protected function makeCompanyStaff(Company $company): User
    {
        $staff = User::factory()->create(['company_id' => $company->id]);

        PermissionsTeam::runAs($company->id, function () use ($staff): void {
            $staff->assignRole(Role::findOrCreate('Staff', 'web'));
        });

        return $staff;
    }

    public function test_it_notifies_only_admins_of_companies_whose_trial_ends_in_exactly_three_days(): void
    {
        Notification::fake();

        $endingSoon = Company::factory()->create(['status' => 'trial', 'trial_ends_at' => now()->addDays(3)]);
        $admin = $this->makeCompanyAdmin($endingSoon);
        $staff = $this->makeCompanyStaff($endingSoon);

        $endingLater = Company::factory()->create(['status' => 'trial', 'trial_ends_at' => now()->addDays(10)]);
        $otherAdmin = $this->makeCompanyAdmin($endingLater);

        $notActive = Company::factory()->create(['status' => 'active', 'trial_ends_at' => now()->addDays(3)]);
        $activeAdmin = $this->makeCompanyAdmin($notActive);

        $this->artisan('notifications:trial-ending')->assertSuccessful();

        Notification::assertSentTo($admin, TrialEndingSoon::class);
        Notification::assertNotSentTo($staff, TrialEndingSoon::class);
        Notification::assertNotSentTo($otherAdmin, TrialEndingSoon::class);
        Notification::assertNotSentTo($activeAdmin, TrialEndingSoon::class);
    }

    public function test_running_it_twice_does_not_notify_the_same_admin_twice(): void
    {
        Mail::fake();
        $company = Company::factory()->create(['status' => 'trial', 'trial_ends_at' => now()->addDays(3)]);
        $admin = $this->makeCompanyAdmin($company);

        $this->artisan('notifications:trial-ending')->assertSuccessful();
        $this->artisan('notifications:trial-ending')->assertSuccessful();

        $this->assertSame(1, $admin->notifications()->where('type', TrialEndingSoon::class)->count());
    }
}
