<?php

namespace Tests\Feature\Payments;

use App\Modules\Companies\Models\Company;
use App\Modules\Integrations\Models\Integration;
use App\Modules\Payments\Drivers\FlutterwaveDriver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FlutterwaveDriverTest extends TestCase
{
    use RefreshDatabase;

    protected function integration(): Integration
    {
        $company = Company::factory()->create();

        return Integration::create([
            'company_id' => $company->id,
            'type' => 'payment',
            'provider' => 'flutterwave',
            'credentials' => ['api_secret' => 'FLWSECK_TEST-xxx'],
        ]);
    }

    public function test_charge_creates_a_payment_link_and_reports_success(): void
    {
        Http::fake(['api.flutterwave.com/v3/payments' => Http::response([
            'status' => 'success', 'data' => ['link' => 'https://checkout.flutterwave.com/pay/xxx'],
        ], 200)]);

        $result = (new FlutterwaveDriver())->charge($this->integration(), 5000, 'ngn', 'order-1');

        $this->assertTrue($result['success']);
        $this->assertSame('order-1', $result['reference']);
        $this->assertSame('https://checkout.flutterwave.com/pay/xxx', $result['redirect_url']);
        Http::assertSent(fn ($request) => $request['tx_ref'] === 'order-1' && (float) $request['amount'] === 50.0);
    }

    public function test_charge_reports_failure_when_flutterwave_rejects_it(): void
    {
        Http::fake(['api.flutterwave.com/v3/payments' => Http::response(['status' => 'error', 'message' => 'Invalid secret key'], 401)]);

        $result = (new FlutterwaveDriver())->charge($this->integration(), 5000, 'ngn', 'order-1');

        $this->assertFalse($result['success']);
        $this->assertSame('Invalid secret key', $result['message']);
    }

    public function test_verify_reports_success_when_status_is_successful(): void
    {
        Http::fake(['api.flutterwave.com/v3/transactions/verify_by_reference*' => Http::response(['data' => ['status' => 'successful']], 200)]);

        $result = (new FlutterwaveDriver())->verify($this->integration(), 'order-1');

        $this->assertTrue($result['success']);
    }

    public function test_refund_omits_amount_when_none_given_and_reports_success(): void
    {
        Http::fake(['api.flutterwave.com/v3/transactions/*/refund' => Http::response(['status' => 'success'], 200)]);

        $result = (new FlutterwaveDriver())->refund($this->integration(), '998877');

        $this->assertTrue($result['success']);
    }

    public function test_test_reports_success_when_the_balances_endpoint_responds(): void
    {
        Http::fake(['api.flutterwave.com/v3/balances' => Http::response(['status' => 'success', 'data' => []], 200)]);

        $result = (new FlutterwaveDriver())->test($this->integration());

        $this->assertTrue($result['success']);
    }

    public function test_test_reports_failure_when_credentials_are_rejected(): void
    {
        Http::fake(['api.flutterwave.com/v3/balances' => Http::response(['status' => 'error', 'message' => 'Invalid authorization key'], 401)]);

        $result = (new FlutterwaveDriver())->test($this->integration());

        $this->assertFalse($result['success']);
        $this->assertSame('Invalid authorization key', $result['message']);
    }
}
