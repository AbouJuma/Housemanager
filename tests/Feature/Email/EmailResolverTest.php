<?php

namespace Tests\Feature\Email;

use App\Modules\Companies\Models\Company;
use App\Modules\Email\Services\EmailResolver;
use App\Modules\Integrations\Models\Integration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class EmailResolverTest extends TestCase
{
    use RefreshDatabase;

    protected EmailResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = app(EmailResolver::class);
    }

    public function test_a_company_with_no_email_integration_uses_the_system_default_mailer(): void
    {
        $company = Company::factory()->create();

        $this->assertSame(config('mail.default'), $this->resolver->mailerFor($company));
    }

    public function test_an_inactive_integration_is_ignored_in_favour_of_the_default_mailer(): void
    {
        $company = Company::factory()->create();
        Integration::create([
            'company_id' => $company->id, 'type' => 'email', 'provider' => 'smtp', 'is_active' => false,
            'credentials' => ['api_key' => 'smtp.example.com'],
        ]);

        $this->assertSame(config('mail.default'), $this->resolver->mailerFor($company));
    }

    public function test_an_active_generic_smtp_integration_registers_the_company_mailer_from_its_own_fields(): void
    {
        $company = Company::factory()->create();
        Integration::create([
            'company_id' => $company->id,
            'type' => 'email',
            'provider' => 'smtp',
            'is_active' => true,
            'credentials' => [
                'api_key' => 'smtp.example.com',
                'api_secret' => '2525',
                'merchant_id' => 'ssl',
                'username' => 'mailer@example.com',
                'password' => 'secret',
            ],
        ]);

        $mailer = $this->resolver->mailerFor($company);

        $this->assertSame('company', $mailer);
        $config = config('mail.mailers.company');
        $this->assertSame('smtp', $config['transport']);
        $this->assertSame('smtp.example.com', $config['host']);
        $this->assertSame(2525, $config['port']);
        $this->assertSame('ssl', $config['encryption']);
        $this->assertSame('mailer@example.com', $config['username']);
        $this->assertSame('secret', $config['password']);
    }

    public function test_a_generic_smtp_integration_with_no_host_throws(): void
    {
        $company = Company::factory()->create();
        Integration::create(['company_id' => $company->id, 'type' => 'email', 'provider' => 'smtp', 'is_active' => true]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/No SMTP host/');

        $this->resolver->mailerFor($company);
    }

    public function test_a_preset_provider_fills_in_the_well_known_host_when_left_blank(): void
    {
        $company = Company::factory()->create();
        $integration = Integration::create([
            'company_id' => $company->id,
            'type' => 'email',
            'provider' => 'gmail',
            'credentials' => ['username' => 'me@gmail.com', 'password' => 'app-password'],
        ]);

        $this->resolver->mailerForIntegration($integration);

        $config = config('mail.mailers.company');
        $this->assertSame('smtp.gmail.com', $config['host']);
        $this->assertSame(587, $config['port']);
        $this->assertSame('tls', $config['encryption']);
        $this->assertSame('me@gmail.com', $config['username']);
    }

    public function test_a_preset_provider_host_can_still_be_overridden(): void
    {
        $company = Company::factory()->create();
        $integration = Integration::create([
            'company_id' => $company->id,
            'type' => 'email',
            'provider' => 'mailgun',
            'credentials' => ['api_key' => 'smtp.eu.mailgun.org'],
        ]);

        $this->resolver->mailerForIntegration($integration);

        $this->assertSame('smtp.eu.mailgun.org', config('mail.mailers.company.host'));
    }

    public function test_encryption_of_none_is_translated_to_a_null_encryption_option(): void
    {
        $company = Company::factory()->create();
        $integration = Integration::create([
            'company_id' => $company->id,
            'type' => 'email',
            'provider' => 'smtp',
            'credentials' => ['api_key' => 'localhost', 'merchant_id' => 'none'],
        ]);

        $this->resolver->mailerForIntegration($integration);

        $this->assertNull(config('mail.mailers.company.encryption'));
    }
}
