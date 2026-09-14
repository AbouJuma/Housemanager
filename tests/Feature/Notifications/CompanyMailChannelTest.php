<?php

namespace Tests\Feature\Notifications;

use App\Models\User;
use App\Modules\Companies\Models\Company;
use App\Modules\Email\Services\EmailResolver;
use App\Modules\Notifications\Channels\CompanyMailChannel;
use App\Modules\Notifications\Notifications\TrialEndingSoon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

class CompanyMailChannelTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_resolves_the_mailer_for_the_notifiables_own_company(): void
    {
        // Mail::fake() intercepts the actual send (no real network call);
        // raw()-based sends don't produce an assertSent()-inspectable
        // Mailable the way a real Mailable class does (see
        // SmtpEmailTesterTest, Module 9, for the same limitation), so
        // what this test verifies is that the channel resolves the
        // right company's mailer - the send itself not throwing proves
        // it got that far.
        Mail::fake();
        $company = Company::factory()->create(['trial_ends_at' => now()->addDays(3)]);
        $admin = User::factory()->create(['company_id' => $company->id, 'email' => 'admin@example.com']);

        $resolver = Mockery::mock(EmailResolver::class);
        $resolver->shouldReceive('mailerFor')->once()->with(Mockery::on(fn ($c) => $c->is($company)))->andReturn('array');

        (new CompanyMailChannel($resolver))->send($admin, new TrialEndingSoon($company));
    }

    public function test_it_uses_the_system_default_mailer_when_the_notifiable_has_no_company(): void
    {
        Mail::fake();
        $company = Company::factory()->create(['trial_ends_at' => now()->addDays(3)]);
        $saasOwner = User::factory()->create(['company_id' => null]);

        $resolver = Mockery::mock(EmailResolver::class);
        $resolver->shouldReceive('mailerFor')->never();

        (new CompanyMailChannel($resolver))->send($saasOwner, new TrialEndingSoon($company));
    }

    public function test_it_does_nothing_when_the_notifiable_has_no_email(): void
    {
        Mail::fake();
        $company = Company::factory()->create(['trial_ends_at' => now()->addDays(3)]);
        $admin = User::factory()->create(['company_id' => $company->id]);
        // The factory requires a value at creation time - null it out
        // in-memory to simulate a user with no reachable address.
        $admin->email = null;

        app(CompanyMailChannel::class)->send($admin, new TrialEndingSoon($company));

        Mail::assertNothingSent();
    }
}
