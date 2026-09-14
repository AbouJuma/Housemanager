<?php

namespace Tests\Feature\Sms;

use App\Modules\Companies\Models\Company;
use App\Modules\Integrations\Models\Integration;
use App\Modules\Sms\Drivers\TwilioDriver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TwilioDriverTest extends TestCase
{
    use RefreshDatabase;

    protected function integration(): Integration
    {
        $company = Company::factory()->create();

        return Integration::create([
            'company_id' => $company->id,
            'type' => 'sms',
            'provider' => 'twilio',
            'credentials' => ['api_key' => 'ACxxx', 'api_secret' => 'token', 'merchant_id' => '+15551234567'],
        ]);
    }

    public function test_send_reports_success_and_returns_the_message_sid(): void
    {
        Http::fake(['api.twilio.com/*' => Http::response(['sid' => 'SMxxx'], 201)]);

        $result = (new TwilioDriver())->send($this->integration(), '+15559876543', 'Hello');

        $this->assertTrue($result['success']);
        $this->assertSame('SMxxx', $result['message_id']);
        Http::assertSent(fn ($request) => $request->url() === 'https://api.twilio.com/2010-04-01/Accounts/ACxxx/Messages.json'
            && $request['To'] === '+15559876543'
            && $request['Body'] === 'Hello');
    }

    public function test_send_reports_failure_with_twilios_error_message(): void
    {
        Http::fake(['api.twilio.com/*' => Http::response(['message' => 'The From phone number is not valid'], 400)]);

        $result = (new TwilioDriver())->send($this->integration(), '+15559876543', 'Hello');

        $this->assertFalse($result['success']);
        $this->assertSame('The From phone number is not valid', $result['message']);
    }

    public function test_test_reports_success_when_the_account_is_fetched(): void
    {
        Http::fake(['api.twilio.com/*' => Http::response(['sid' => 'ACxxx'], 200)]);

        $result = (new TwilioDriver())->test($this->integration());

        $this->assertTrue($result['success']);
    }

    public function test_test_reports_failure_when_credentials_are_rejected(): void
    {
        Http::fake(['api.twilio.com/*' => Http::response(['message' => 'Authenticate'], 401)]);

        $result = (new TwilioDriver())->test($this->integration());

        $this->assertFalse($result['success']);
        $this->assertSame('Authenticate', $result['message']);
    }
}
