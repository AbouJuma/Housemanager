<?php

namespace Tests\Feature\Payments;

use App\Modules\Companies\Models\Company;
use App\Modules\Integrations\Models\Integration;
use App\Modules\Payments\Drivers\PayPalDriver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PayPalDriverTest extends TestCase
{
    use RefreshDatabase;

    protected function integration(string $mode = 'sandbox'): Integration
    {
        $company = Company::factory()->create();

        return Integration::create([
            'company_id' => $company->id,
            'type' => 'payment',
            'provider' => 'paypal',
            'mode' => $mode,
            'credentials' => ['api_key' => 'client-id', 'api_secret' => 'client-secret'],
        ]);
    }

    protected function fakeToken(string $host = 'api-m.sandbox.paypal.com'): void
    {
        Http::fake(["{$host}/v1/oauth2/token" => Http::response(['access_token' => 'A21AA-token'], 200)]);
    }

    public function test_charge_creates_an_order_and_returns_the_approve_link(): void
    {
        Http::fake([
            'api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'A21AA-token'], 200),
            'api-m.sandbox.paypal.com/v2/checkout/orders' => Http::response([
                'id' => 'ORDER123',
                'status' => 'CREATED',
                'links' => [['rel' => 'approve', 'href' => 'https://paypal.com/approve/ORDER123']],
            ], 201),
        ]);

        $result = (new PayPalDriver())->charge($this->integration(), 5000, 'usd', 'order-1');

        $this->assertTrue($result['success']);
        $this->assertSame('ORDER123', $result['reference']);
        $this->assertSame('https://paypal.com/approve/ORDER123', $result['redirect_url']);
        Http::assertSent(fn ($request) => $request->hasHeader('PayPal-Request-Id', 'order-1'));
    }

    public function test_charge_uses_the_live_host_when_mode_is_live(): void
    {
        $this->fakeToken('api-m.paypal.com');
        Http::fake(['api-m.paypal.com/v2/checkout/orders' => Http::response(['id' => 'X', 'status' => 'CREATED', 'links' => []], 201)]);

        (new PayPalDriver())->charge($this->integration('live'), 5000, 'usd', 'order-1');

        Http::assertSent(fn ($request) => str_starts_with($request->url(), 'https://api-m.paypal.com'));
    }

    public function test_charge_reports_failure_when_the_token_request_fails(): void
    {
        Http::fake(['api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['error' => 'invalid_client'], 401)]);

        $result = (new PayPalDriver())->charge($this->integration(), 5000, 'usd', 'order-1');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('authenticate', $result['message']);
    }

    public function test_verify_reports_success_when_status_is_completed(): void
    {
        $this->fakeToken();
        Http::fake(['api-m.sandbox.paypal.com/v2/checkout/orders/*' => Http::response(['status' => 'COMPLETED'], 200)]);

        $result = (new PayPalDriver())->verify($this->integration(), 'ORDER123');

        $this->assertTrue($result['success']);
    }

    public function test_refund_includes_amount_only_when_both_amount_and_currency_are_given(): void
    {
        $this->fakeToken();
        Http::fake(['api-m.sandbox.paypal.com/v2/payments/captures/*/refund' => Http::response(['id' => 'REFUND1'], 201)]);

        $result = (new PayPalDriver())->refund($this->integration(), 'CAPTURE1', 2500, 'usd');

        $this->assertTrue($result['success']);
        Http::assertSent(fn ($request) => ($request['amount']['value'] ?? null) === '25.00' && $request['amount']['currency_code'] === 'USD');
    }

    public function test_test_reports_success_when_a_token_is_issued(): void
    {
        $this->fakeToken();

        $result = (new PayPalDriver())->test($this->integration());

        $this->assertTrue($result['success']);
    }

    public function test_test_reports_failure_when_credentials_are_rejected(): void
    {
        Http::fake(['api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['error' => 'invalid_client'], 401)]);

        $result = (new PayPalDriver())->test($this->integration());

        $this->assertFalse($result['success']);
    }
}
