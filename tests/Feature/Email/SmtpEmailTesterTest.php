<?php

namespace Tests\Feature\Email;

use App\Models\User;
use App\Modules\Companies\Models\Company;
use App\Modules\Email\Services\EmailResolver;
use App\Modules\Email\Testers\SmtpEmailTester;
use App\Modules\Integrations\Models\Integration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class SmtpEmailTesterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sends_a_real_test_email_to_the_acting_user_and_reports_success(): void
    {
        Mail::fake();
        $company = Company::factory()->create();
        $user = User::factory()->create(['email' => 'admin@example.com']);
        $integration = Integration::create([
            'company_id' => $company->id, 'type' => 'email', 'provider' => 'gmail',
            'credentials' => ['username' => 'me@gmail.com', 'password' => 'app-password'],
        ]);

        $this->actingAs($user);
        $result = app(SmtpEmailTester::class)->test($integration);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('admin@example.com', $result['message']);
    }

    public function test_it_falls_back_to_the_companys_own_email_when_no_user_is_authenticated(): void
    {
        Mail::fake();
        $company = Company::factory()->create(['email' => 'contact@acme.test']);
        $integration = Integration::create([
            'company_id' => $company->id, 'type' => 'email', 'provider' => 'gmail',
            'credentials' => ['username' => 'me@gmail.com', 'password' => 'app-password'],
        ]);

        $result = app(SmtpEmailTester::class)->test($integration);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('contact@acme.test', $result['message']);
    }

    public function test_it_reports_failure_when_there_is_no_recipient_available(): void
    {
        $company = Company::factory()->create(['email' => null]);
        $integration = Integration::create(['company_id' => $company->id, 'type' => 'email', 'provider' => 'gmail']);

        $result = app(SmtpEmailTester::class)->test($integration);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('No recipient', $result['message']);
    }

    public function test_it_reports_failure_when_the_resolver_cannot_build_a_mailer(): void
    {
        $company = Company::factory()->create();
        $integration = Integration::create(['company_id' => $company->id, 'type' => 'email', 'provider' => 'smtp']);

        $resolver = Mockery::mock(EmailResolver::class);
        $resolver->shouldReceive('mailerForIntegration')->once()->with($integration)->andThrow(new RuntimeException('no host configured'));

        $result = (new SmtpEmailTester($resolver))->test($integration);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('no host configured', $result['message']);
    }
}
