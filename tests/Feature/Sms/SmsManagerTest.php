<?php

namespace Tests\Feature\Sms;

use App\Modules\Companies\Models\Company;
use App\Modules\Integrations\Models\Integration;
use App\Modules\Sms\Drivers\TwilioDriver;
use App\Modules\Sms\Services\SmsManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SmsManagerTest extends TestCase
{
    use RefreshDatabase;

    protected SmsManager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = app(SmsManager::class);
    }

    public function test_send_reports_failure_when_no_provider_is_active(): void
    {
        $company = Company::factory()->create();

        $result = $this->manager->send($company, '255700000000', 'Hello');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('No active SMS provider', $result['message']);
    }

    public function test_send_ignores_an_inactive_integration(): void
    {
        $company = Company::factory()->create();
        Integration::create([
            'company_id' => $company->id, 'type' => 'sms', 'provider' => 'twilio', 'is_active' => false,
            'credentials' => ['api_key' => 'ACxxx', 'api_secret' => 'token', 'merchant_id' => '+15551234567'],
        ]);

        $result = $this->manager->send($company, '255700000000', 'Hello');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('No active SMS provider', $result['message']);
    }

    public function test_send_delegates_to_the_active_providers_driver(): void
    {
        Http::fake(['api.twilio.com/*' => Http::response(['sid' => 'SMxxx'], 201)]);

        $company = Company::factory()->create();
        Integration::create([
            'company_id' => $company->id, 'type' => 'sms', 'provider' => 'twilio', 'is_active' => true,
            'credentials' => ['api_key' => 'ACxxx', 'api_secret' => 'token', 'merchant_id' => '+15551234567'],
        ]);

        $result = $this->manager->send($company, '255700000000', 'Hello');

        $this->assertTrue($result['success']);
        $this->assertSame('SMxxx', $result['message_id']);
    }

    public function test_driver_for_resolves_every_catalog_provider_including_nextsms(): void
    {
        foreach (array_keys(config('integrations.sms_drivers')) as $provider) {
            $integration = Integration::make(['type' => 'sms', 'provider' => $provider]);

            $this->assertNotNull($this->manager->driverFor($integration), "No driver resolved for [{$provider}]");
        }
    }

    public function test_driver_for_the_twilio_provider_is_a_twilio_driver_instance(): void
    {
        $integration = Integration::make(['type' => 'sms', 'provider' => 'twilio']);

        $this->assertInstanceOf(TwilioDriver::class, $this->manager->driverFor($integration));
    }
}
