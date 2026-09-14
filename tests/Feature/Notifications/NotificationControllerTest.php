<?php

namespace Tests\Feature\Notifications;

use App\Models\User;
use App\Modules\Companies\Models\Company;
use App\Modules\Notifications\Notifications\TrialEndingSoon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_cannot_view_notifications(): void
    {
        $this->get(route('notifications.index'))->assertRedirect(route('login'));
    }

    public function test_a_user_sees_only_their_own_notifications(): void
    {
        Mail::fake();
        $company = Company::factory()->create(['trial_ends_at' => now()->addDays(3)]);
        $mine = User::factory()->create(['company_id' => $company->id]);
        $someoneElses = User::factory()->create(['company_id' => $company->id]);

        $mine->notify(new TrialEndingSoon($company));
        $someoneElses->notify(new TrialEndingSoon($company));

        $response = $this->actingAs($mine)->get(route('notifications.index'));

        $response->assertOk();
        $this->assertCount(1, $response->viewData('notifications'));
    }

    public function test_recent_returns_the_unread_count_and_latest_notifications_as_json(): void
    {
        Mail::fake();
        $company = Company::factory()->create(['trial_ends_at' => now()->addDays(3)]);
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->notify(new TrialEndingSoon($company));

        $response = $this->actingAs($user)->getJson(route('notifications.recent'));

        $response->assertOk();
        $response->assertJsonPath('unread_count', 1);
        $response->assertJsonCount(1, 'notifications');
        $response->assertJsonPath('notifications.0.title', 'Trial ending soon');
    }

    public function test_mark_read_marks_only_the_given_notification_for_the_acting_user(): void
    {
        Mail::fake();
        $company = Company::factory()->create(['trial_ends_at' => now()->addDays(3)]);
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->notify(new TrialEndingSoon($company));
        $notification = $user->notifications()->first();

        $this->actingAs($user)->post(route('notifications.read', $notification->id))->assertRedirect();

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_a_user_cannot_mark_another_users_notification_read(): void
    {
        Mail::fake();
        $company = Company::factory()->create(['trial_ends_at' => now()->addDays(3)]);
        $owner = User::factory()->create(['company_id' => $company->id]);
        $intruder = User::factory()->create(['company_id' => $company->id]);
        $owner->notify(new TrialEndingSoon($company));
        $notification = $owner->notifications()->first();

        $this->actingAs($intruder)->post(route('notifications.read', $notification->id))->assertNotFound();

        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_mark_all_read_marks_every_unread_notification_for_the_acting_user(): void
    {
        Mail::fake();
        $company = Company::factory()->create(['trial_ends_at' => now()->addDays(3)]);
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->notify(new TrialEndingSoon($company));
        $user->notify(new TrialEndingSoon($company));

        $this->actingAs($user)->post(route('notifications.mark-all-read'))->assertRedirect();

        $this->assertSame(0, $user->unreadNotifications()->count());
    }
}
