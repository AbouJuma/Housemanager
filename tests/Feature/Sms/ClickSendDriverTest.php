<?php

namespace Tests\Feature\Sms;

use App\Modules\Companies\Models\Company;
use App\Modules\Integrations\Models\Integration;
use App\Modules\Sms\Drivers\ClickSendDriver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ClickSendDriverTest extends TestCase
{
    use RefreshDatabase;

    protected function integration(): Integration
    {
        $company = Company::factory()->create();

        return Integration::create([
            'company_id' => $company->id,
            'type' => 'sms',
            'provider' => 'clicksend',
            'credentials' => ['api_key' => 'user', 'api_secret' => 'key', 'merchant_id' => 'MyBrand'],
        ]);
    }

    public function test_send_reports_success_and_returns_the_message_id(): void
    {
        Http::fake(['rest.clicksend.com/*' => Http::response([
            'data' => ['messages' => [['status' => 'SUCCESS', 'message_id' => 'abc-123']]],
        ], 200)]);

        $result = (new ClickSendDriver())->send($this->integration(), '+61411111111', 'Hello');

        $this->assertTrue($result['success']);
        $this->assertSame('abc-123', $result['message_id']);
    }

    public function test_send_reports_failure_when_clicksend_marks_the_message_failed(): void
    {
        Http::fake(['rest.clicksend.com/*' => Http::response([
            'response_msg' => 'Insufficient credit',
            'data' => ['messages' => [['status' => 'FAILED']]],
        ], 200)]);

        $result = (new ClickSendDriver())->send($this->integration(), '+61411111111', 'Hello');

        $this->assertFalse($result['success']);
        $this->assertSame('Insufficient credit', $result['message']);
    }

    public function test_test_reports_success_when_the_account_endpoint_responds(): void
    {
        Http::fake(['rest.clicksend.com/*' => Http::response(['data' => []], 200)]);

        $result = (new ClickSendDriver())->test($this->integration());

        $this->assertTrue($result['success']);
    }

    public function test_test_reports_failure_when_credentials_are_rejected(): void
    {
        Http::fake(['rest.clicksend.com/*' => Http::response(['response_msg' => 'Access token expired'], 401)]);

        $result = (new ClickSendDriver())->test($this->integration());

        $this->assertFalse($result['success']);
        $this->assertSame('Access token expired', $result['message']);
    }
}
