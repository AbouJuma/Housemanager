<?php

namespace Tests\Feature\Payments;

use App\Modules\Companies\Models\Company;
use App\Modules\Integrations\Models\Integration;
use App\Modules\Payments\Drivers\SelcomDriver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SelcomDriverTest extends TestCase
{
    use RefreshDatabase;

    protected function integration(): Integration
    {
        $company = Company::factory()->create();

        return Integration::create([
            'company_id' => $company->id,
            'type' => 'payment',
            'provider' => 'selcom',
            'credentials' => ['api_key' => 'vendor-key', 'api_secret' => 'vendor-secret', 'merchant_id' => 'VENDOR1'],
        ]);
    }

    public function test_charge_signs_the_request_and_returns_a_redirect_url_on_success(): void
    {
        Http::fake(['apigw.selcommobile.com/*' => Http::response([
            'resultcode' => '000',
            'data' => [['payment_gateway_url' => 'https://selcom.net/pay/xyz']],
        ], 200)]);

        $result = (new SelcomDriver())->charge($this->integration(), 5000, 'tzs', 'order-1');

        $this->assertTrue($result['success']);
        $this->assertSame('https://selcom.net/pay/xyz', $result['redirect_url']);
        Http::assertSent(fn ($request) => $request->hasHeader('Digest') && $request->hasHeader('Digest-Method', 'HS256') && $request['order_id'] === 'order-1');
    }

    public function test_charge_reports_failure_when_selcom_rejects_it(): void
    {
        Http::fake(['apigw.selcommobile.com/*' => Http::response(['resultcode' => '999', 'message' => 'Invalid signature'], 400)]);

        $result = (new SelcomDriver())->charge($this->integration(), 5000, 'tzs', 'order-1');

        $this->assertFalse($result['success']);
        $this->assertSame('Invalid signature', $result['message']);
    }

    public function test_verify_reports_success_when_status_is_completed(): void
    {
        Http::fake(['apigw.selcommobile.com/*' => Http::response(['data' => [['payment_status' => 'COMPLETED']]], 200)]);

        $result = (new SelcomDriver())->verify($this->integration(), 'order-1');

        $this->assertTrue($result['success']);
    }

    public function test_refund_reports_that_it_is_not_supported(): void
    {
        Http::fake();

        $result = (new SelcomDriver())->refund($this->integration(), 'order-1');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('does not support refunds', $result['message']);
        Http::assertNothingSent();
    }

    public function test_test_reports_success_when_the_gateway_accepts_the_signed_request(): void
    {
        Http::fake(['apigw.selcommobile.com/*' => Http::response(['resultcode' => '134', 'message' => 'Order not found'], 200)]);

        $result = (new SelcomDriver())->test($this->integration());

        $this->assertTrue($result['success']);
    }

    public function test_test_reports_failure_when_credentials_are_rejected(): void
    {
        Http::fake(['apigw.selcommobile.com/*' => Http::response(['resultcode' => '999'], 401)]);

        $result = (new SelcomDriver())->test($this->integration());

        $this->assertFalse($result['success']);
    }
}
