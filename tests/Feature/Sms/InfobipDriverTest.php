<?php

namespace Tests\Feature\Sms;

use App\Modules\Companies\Models\Company;
use App\Modules\Integrations\Models\Integration;
use App\Modules\Sms\Drivers\InfobipDriver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InfobipDriverTest extends TestCase
{
    use RefreshDatabase;

    protected function integration(array $credentials = []): Integration
    {
        $company = Company::factory()->create();

        return Integration::create([
            'company_id' => $company->id,
            'type' => 'sms',
            'provider' => 'infobip',
            'credentials' => array_merge([
                'api_key' => 'secret-key',
                'merchant_id' => 'MyCompany',
                'username' => 'abc123.api.infobip.com',
            ], $credentials),
        ]);
    }

    public function test_send_uses_the_accounts_configured_base_url_and_reports_success(): void
    {
        Http::fake(['abc123.api.infobip.com/*' => Http::response([
            'messages' => [['messageId' => 'MSG1', 'status' => ['groupName' => 'PENDING']]],
        ], 200)]);

        $result = (new InfobipDriver())->send($this->integration(), '255700000000', 'Hello');

        $this->assertTrue($result['success']);
        $this->assertSame('MSG1', $result['message_id']);
        Http::assertSent(fn ($request) => $request->url() === 'https://abc123.api.infobip.com/sms/2/text/advanced');
    }

    public function test_send_falls_back_to_the_shared_host_when_no_base_url_is_set(): void
    {
        Http::fake(['api.infobip.com/*' => Http::response(['messages' => [['messageId' => 'MSG2']]], 200)]);

        $result = (new InfobipDriver())->send($this->integration(['username' => null]), '255700000000', 'Hello');

        $this->assertTrue($result['success']);
        Http::assertSent(fn ($request) => $request->url() === 'https://api.infobip.com/sms/2/text/advanced');
    }

    public function test_send_reports_infobips_service_exception_message_on_failure(): void
    {
        Http::fake(['abc123.api.infobip.com/*' => Http::response([
            'requestError' => ['serviceException' => ['text' => 'Invalid login details']],
        ], 401)]);

        $result = (new InfobipDriver())->send($this->integration(), '255700000000', 'Hello');

        $this->assertFalse($result['success']);
        $this->assertSame('Invalid login details', $result['message']);
    }

    public function test_test_reports_success_when_the_balance_endpoint_responds(): void
    {
        Http::fake(['abc123.api.infobip.com/*' => Http::response(['balance' => 10.5], 200)]);

        $result = (new InfobipDriver())->test($this->integration());

        $this->assertTrue($result['success']);
    }
}
