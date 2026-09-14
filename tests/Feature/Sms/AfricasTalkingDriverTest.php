<?php

namespace Tests\Feature\Sms;

use App\Modules\Companies\Models\Company;
use App\Modules\Integrations\Models\Integration;
use App\Modules\Sms\Drivers\AfricasTalkingDriver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AfricasTalkingDriverTest extends TestCase
{
    use RefreshDatabase;

    protected function integration(): Integration
    {
        $company = Company::factory()->create();

        return Integration::create([
            'company_id' => $company->id,
            'type' => 'sms',
            'provider' => 'africas_talking',
            'credentials' => ['api_key' => 'sandbox', 'api_secret' => 'atsk_xxx', 'merchant_id' => 'MyShortCode'],
        ]);
    }

    public function test_send_reports_success_and_returns_the_message_id(): void
    {
        Http::fake(['api.africastalking.com/version1/messaging' => Http::response([
            'SMSMessageData' => ['Recipients' => [['status' => 'Success', 'messageId' => 'ATXid_123']]],
        ], 200)]);

        $result = (new AfricasTalkingDriver())->send($this->integration(), '+255700000000', 'Hello');

        $this->assertTrue($result['success']);
        $this->assertSame('ATXid_123', $result['message_id']);
        Http::assertSent(fn ($request) => $request['username'] === 'sandbox' && $request->hasHeader('apiKey', 'atsk_xxx'));
    }

    public function test_send_reports_failure_with_the_recipients_status(): void
    {
        Http::fake(['api.africastalking.com/version1/messaging' => Http::response([
            'SMSMessageData' => ['Recipients' => [['status' => 'InvalidPhoneNumber']]],
        ], 200)]);

        $result = (new AfricasTalkingDriver())->send($this->integration(), 'not-a-number', 'Hello');

        $this->assertFalse($result['success']);
        $this->assertSame('InvalidPhoneNumber', $result['message']);
    }

    public function test_test_reports_success_when_the_user_endpoint_responds(): void
    {
        Http::fake(['api.africastalking.com/version1/user*' => Http::response(['UserData' => []], 200)]);

        $result = (new AfricasTalkingDriver())->test($this->integration());

        $this->assertTrue($result['success']);
    }

    public function test_test_reports_failure_when_credentials_are_rejected(): void
    {
        Http::fake(['api.africastalking.com/version1/user*' => Http::response([], 401)]);

        $result = (new AfricasTalkingDriver())->test($this->integration());

        $this->assertFalse($result['success']);
    }
}
