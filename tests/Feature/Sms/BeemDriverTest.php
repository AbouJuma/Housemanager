<?php

namespace Tests\Feature\Sms;

use App\Modules\Companies\Models\Company;
use App\Modules\Integrations\Models\Integration;
use App\Modules\Sms\Drivers\BeemDriver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BeemDriverTest extends TestCase
{
    use RefreshDatabase;

    protected function integration(): Integration
    {
        $company = Company::factory()->create();

        return Integration::create([
            'company_id' => $company->id,
            'type' => 'sms',
            'provider' => 'beem',
            'credentials' => ['api_key' => 'key', 'api_secret' => 'secret', 'merchant_id' => 'MYCOMPANY'],
        ]);
    }

    public function test_send_reports_success_and_returns_the_request_id(): void
    {
        Http::fake(['apisms.beem.africa/v1/send' => Http::response(['successful' => true, 'request_id' => 999], 200)]);

        $result = (new BeemDriver())->send($this->integration(), '255700000000', 'Hello');

        $this->assertTrue($result['success']);
        $this->assertSame(999, $result['message_id']);
    }

    public function test_send_reports_failure_when_beem_rejects_it(): void
    {
        Http::fake(['apisms.beem.africa/v1/send' => Http::response(['message' => 'Insufficient balance'], 400)]);

        $result = (new BeemDriver())->send($this->integration(), '255700000000', 'Hello');

        $this->assertFalse($result['success']);
        $this->assertSame('Insufficient balance', $result['message']);
    }

    public function test_test_reports_success_when_the_balance_endpoint_responds(): void
    {
        Http::fake(['apisms.beem.africa/public/v1/vendors/balance' => Http::response(['data' => ['credit_balance' => '100']], 200)]);

        $result = (new BeemDriver())->test($this->integration());

        $this->assertTrue($result['success']);
    }

    public function test_test_reports_failure_when_credentials_are_rejected(): void
    {
        Http::fake(['apisms.beem.africa/public/v1/vendors/balance' => Http::response([], 401)]);

        $result = (new BeemDriver())->test($this->integration());

        $this->assertFalse($result['success']);
    }
}
