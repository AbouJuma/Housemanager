<?php

namespace Tests\Feature\Payments;

use App\Modules\Companies\Models\Company;
use App\Modules\Integrations\Models\Integration;
use App\Modules\Payments\Drivers\MpesaDriver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MpesaDriverTest extends TestCase
{
    use RefreshDatabase;

    protected function integration(string $mode = 'sandbox'): Integration
    {
        $company = Company::factory()->create();

        return Integration::create([
            'company_id' => $company->id,
            'type' => 'payment',
            'provider' => 'mpesa',
            'mode' => $mode,
            'credentials' => ['api_key' => 'consumer-key', 'api_secret' => 'consumer-secret', 'merchant_id' => '174379', 'username' => 'passkey'],
        ]);
    }

    protected function fakeToken(string $host): void
    {
        Http::fake(["{$host}/oauth/v1/generate*" => Http::response(['access_token' => 'token-123'], 200)]);
    }

    public function test_charge_sends_an_stk_push_and_returns_the_checkout_request_id(): void
    {
        Http::fake([
            'sandbox.safaricom.co.tz/oauth/v1/generate*' => Http::response(['access_token' => 'token-123'], 200),
            'sandbox.safaricom.co.tz/mpesa/stkpush/v1/processrequest' => Http::response([
                'ResponseCode' => '0', 'CheckoutRequestID' => 'ws_CO_123', 'CustomerMessage' => 'Success. Request accepted.',
            ], 200),
        ]);

        $result = (new MpesaDriver())->charge($this->integration(), 5000, 'tzs', 'order-1', ['phone' => '255700000000']);

        $this->assertTrue($result['success']);
        $this->assertSame('ws_CO_123', $result['reference']);
        Http::assertSent(fn ($request) => str_starts_with($request->url(), 'https://sandbox.safaricom.co.tz'));
    }

    public function test_charge_uses_the_production_host_when_mode_is_live(): void
    {
        $this->fakeToken('api.safaricom.co.tz');
        Http::fake(['api.safaricom.co.tz/mpesa/stkpush/v1/processrequest' => Http::response(['ResponseCode' => '0', 'CheckoutRequestID' => 'X'], 200)]);

        (new MpesaDriver())->charge($this->integration('live'), 5000, 'tzs', 'order-1');

        Http::assertSent(fn ($request) => str_starts_with($request->url(), 'https://api.safaricom.co.tz'));
    }

    public function test_charge_reports_failure_when_authentication_fails(): void
    {
        Http::fake(['sandbox.safaricom.co.tz/oauth/v1/generate*' => Http::response(['errorMessage' => 'Invalid credentials'], 401)]);

        $result = (new MpesaDriver())->charge($this->integration(), 5000, 'tzs', 'order-1');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('authenticate', $result['message']);
    }

    public function test_verify_reports_success_when_result_code_is_zero(): void
    {
        $this->fakeToken('sandbox.safaricom.co.tz');
        Http::fake(['sandbox.safaricom.co.tz/mpesa/stkpushquery/v1/query' => Http::response(['ResultCode' => '0', 'ResultDesc' => 'The service request is processed successfully.'], 200)]);

        $result = (new MpesaDriver())->verify($this->integration(), 'ws_CO_123');

        $this->assertTrue($result['success']);
    }

    public function test_refund_sends_a_reversal_request(): void
    {
        $this->fakeToken('sandbox.safaricom.co.tz');
        Http::fake(['sandbox.safaricom.co.tz/mpesa/reversal/v1/request' => Http::response(['ResponseCode' => '0'], 200)]);

        $result = (new MpesaDriver())->refund($this->integration(), 'QGR12345', 2500);

        $this->assertTrue($result['success']);
        Http::assertSent(fn ($request) => ($request['TransactionID'] ?? null) === 'QGR12345');
    }

    public function test_test_reports_success_when_a_token_is_issued(): void
    {
        $this->fakeToken('sandbox.safaricom.co.tz');

        $result = (new MpesaDriver())->test($this->integration());

        $this->assertTrue($result['success']);
    }
}
