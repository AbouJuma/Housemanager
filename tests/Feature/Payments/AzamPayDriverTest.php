<?php

namespace Tests\Feature\Payments;

use App\Modules\Companies\Models\Company;
use App\Modules\Integrations\Models\Integration;
use App\Modules\Payments\Drivers\AzamPayDriver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AzamPayDriverTest extends TestCase
{
    use RefreshDatabase;

    protected function integration(string $mode = 'sandbox'): Integration
    {
        $company = Company::factory()->create();

        return Integration::create([
            'company_id' => $company->id,
            'type' => 'payment',
            'provider' => 'azampay',
            'mode' => $mode,
            'credentials' => ['api_key' => 'client-id', 'api_secret' => 'client-secret', 'merchant_id' => 'MyApp'],
        ]);
    }

    protected function fakeToken(string $host = 'authenticator-sandbox.azampay.co.tz'): void
    {
        Http::fake(["{$host}/AppRegistration/GenerateToken" => Http::response(['data' => ['accessToken' => 'token-123']], 200)]);
    }

    public function test_charge_requests_a_checkout_and_reports_success(): void
    {
        $this->fakeToken();
        Http::fake(['sandbox.azampay.co.tz/azampay/mno/checkout' => Http::response([
            'success' => true, 'message' => 'Request accepted', 'transactionId' => 'AZ123',
        ], 200)]);

        $result = (new AzamPayDriver())->charge($this->integration(), 5000, 'tzs', 'order-1', ['phone' => '255700000000']);

        $this->assertTrue($result['success']);
        $this->assertSame('AZ123', $result['reference']);
    }

    public function test_charge_uses_the_live_hosts_when_mode_is_live(): void
    {
        $this->fakeToken('authenticator.azampay.co.tz');
        Http::fake(['checkout.azampay.co.tz/azampay/mno/checkout' => Http::response(['success' => true, 'transactionId' => 'X'], 200)]);

        (new AzamPayDriver())->charge($this->integration('live'), 5000, 'tzs', 'order-1');

        Http::assertSent(fn ($request) => str_starts_with($request->url(), 'https://checkout.azampay.co.tz'));
    }

    public function test_charge_reports_failure_when_authentication_fails(): void
    {
        Http::fake(['authenticator-sandbox.azampay.co.tz/*' => Http::response([], 401)]);

        $result = (new AzamPayDriver())->charge($this->integration(), 5000, 'tzs', 'order-1');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('authenticate', $result['message']);
    }

    public function test_verify_reports_success_when_status_is_success(): void
    {
        $this->fakeToken();
        Http::fake(['sandbox.azampay.co.tz/azampay/transactions/*' => Http::response(['transactionStatus' => 'Success'], 200)]);

        $result = (new AzamPayDriver())->verify($this->integration(), 'AZ123');

        $this->assertTrue($result['success']);
    }

    public function test_refund_reports_that_it_is_not_supported(): void
    {
        Http::fake();

        $result = (new AzamPayDriver())->refund($this->integration(), 'AZ123');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('does not support refunds', $result['message']);
        Http::assertNothingSent();
    }

    public function test_test_reports_success_when_a_token_is_issued(): void
    {
        $this->fakeToken();

        $result = (new AzamPayDriver())->test($this->integration());

        $this->assertTrue($result['success']);
    }
}
