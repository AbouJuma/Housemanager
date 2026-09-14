<?php

namespace Tests\Feature\Notifications;

use App\Models\User;
use App\Modules\Companies\Models\Company;
use App\Modules\Notifications\Channels\CompanySmsChannel;
use App\Modules\Sms\Services\SmsManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Notification;
use Mockery;
use Tests\TestCase;

class CompanySmsChannelTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_delegates_to_sms_manager_when_the_notifiable_has_a_phone_and_company(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->create(['company_id' => $company->id, 'phone' => '+255700000000']);
        $notification = new class extends Notification
        {
            public function via($n): array
            {
                return ['company-sms'];
            }

            public function toCompanySms($n): string
            {
                return 'Hello from the test.';
            }
        };

        $manager = Mockery::mock(SmsManager::class);
        $manager->shouldReceive('send')->once()->with(
            Mockery::on(fn ($c) => $c->is($company)),
            '+255700000000',
            'Hello from the test.',
        );

        (new CompanySmsChannel($manager))->send($admin, $notification);
    }

    public function test_it_does_nothing_when_the_notifiable_has_no_phone(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->create(['company_id' => $company->id, 'phone' => null]);
        $notification = new class extends Notification
        {
            public function via($n): array
            {
                return ['company-sms'];
            }

            public function toCompanySms($n): string
            {
                return 'Hello.';
            }
        };

        $manager = Mockery::mock(SmsManager::class);
        $manager->shouldReceive('send')->never();

        (new CompanySmsChannel($manager))->send($admin, $notification);
    }

    public function test_it_does_nothing_when_the_notification_has_no_company_sms_representation(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->create(['company_id' => $company->id, 'phone' => '+255700000000']);
        $notification = new class extends Notification
        {
            public function via($n): array
            {
                return ['database'];
            }
        };

        $manager = Mockery::mock(SmsManager::class);
        $manager->shouldReceive('send')->never();

        (new CompanySmsChannel($manager))->send($admin, $notification);
    }
}
