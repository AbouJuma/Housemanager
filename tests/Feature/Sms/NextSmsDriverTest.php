<?php

namespace Tests\Feature\Sms;

use App\Modules\Companies\Models\Company;
use App\Modules\Integrations\Contracts\IntegrationTester;
use App\Modules\Integrations\Models\Integration;
use App\Modules\Sms\Drivers\NextSmsDriver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NextSmsDriverTest extends TestCase
{
    use RefreshDatabase;

    protected function integration(): Integration
    {
        $company = Company::factory()->create();

        return Integration::create([
            'company_id' => $company->id,
            'type' => 'sms',
            'provider' => 'nextsms',
            'credentials' => ['api_key' => 'user', 'api_secret' => 'pass', 'merchant_id' => 'MYCOMPANY'],
        ]);
    }

    public function test_send_reports_success(): void
    {
        Http::fake(['messaging-service.co.tz/*' => Http::response(['messages' => [['messageId' => 'abc']]], 200)]);

        $result = (new NextSmsDriver())->send($this->integration(), '255700000000', 'Hello');

        $this->assertTrue($result['success']);
        $this->assertSame('abc', $result['message_id']);
    }

    public function test_send_reports_failure(): void
    {
        Http::fake(['messaging-service.co.tz/*' => Http::response('Unauthorized', 401)]);

        $result = (new NextSmsDriver())->send($this->integration(), '255700000000', 'Hello');

        $this->assertFalse($result['success']);
    }

    public function test_it_implements_integration_tester_and_is_registered(): void
    {
        $this->assertInstanceOf(IntegrationTester::class, new NextSmsDriver());
        $this->assertArrayHasKey('nextsms', config('integrations.testers'));
    }

    public function test_test_reports_success_when_the_balance_endpoint_responds(): void
    {
        Http::fake(['messaging-service.co.tz/*' => Http::response(['balance' => 100], 200)]);

        $result = (new NextSmsDriver())->test($this->integration());

        $this->assertTrue($result['success']);
    }

    public function test_test_reports_failure_when_credentials_are_rejected(): void
    {
        Http::fake(['messaging-service.co.tz/*' => Http::response('Unauthorized', 401)]);

        $result = (new NextSmsDriver())->test($this->integration());

        $this->assertFalse($result['success']);
        $this->assertSame('NextSMS rejected these credentials.', $result['message']);
    }
}
