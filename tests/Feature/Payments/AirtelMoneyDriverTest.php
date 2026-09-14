<?php

namespace Tests\Feature\Payments;

use App\Modules\Companies\Models\Company;
use App\Modules\Integrations\Models\Integration;
use App\Modules\Payments\Drivers\AirtelMoneyDriver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AirtelMoneyDriverTest extends TestCase
{
    use RefreshDatabase;

    protected function integration(): Integration
    {
        $company = Company::factory()->create();

        return Integration::create([
            'company_id' => $company->id,
            'type' => 'payment',
            'provider' => 'airtel_money',
            'credentials' => ['api_key' => 'client-id', 'api_secret' => 'client-secret', 'username' => 'TZ', 'password' => 'TZS'],
        ]);
    }

    protected function fakeToken(): void
    {
        Http::fake(['openapi.airtel.africa/auth/oauth2/token' => Http::response(['access_token' => 'token-123'], 200)]);
    }

    public function test_charge_requests_a_collection_and_reports_success(): void
    {
        $this->fakeToken();
        Http::fake(['openapi.airtel.africa/merchant/v1/payments/' => Http::response([
            'status' => ['success' => true, 'message' => 'Accepted'],
            'data' => ['transaction' => ['id' => 'AM123']],
        ], 200)]);

        $result = (new AirtelMoneyDriver())->charge($this->integration(), 5000, 'tzs', 'order-1', ['phone' => '255700000000']);

        $this->assertTrue($result['success']);
        $this->assertSame('AM123', $result['reference']);
        Http::assertSent(fn ($request) => $request->hasHeader('X-Country', 'TZ') && $request->hasHeader('X-Currency', 'TZS'));
    }

    public function test_charge_reports_failure_when_authentication_fails(): void
    {
        Http::fake(['openapi.airtel.africa/auth/oauth2/token' => Http::response([], 401)]);

        $result = (new AirtelMoneyDriver())->charge($this->integration(), 5000, 'tzs', 'order-1');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('authenticate', $result['message']);
    }

    public function test_verify_reports_success_when_status_is_ts(): void
    {
        $this->fakeToken();
        Http::fake(['openapi.airtel.africa/standard/v1/payments/*' => Http::response(['data' => ['transaction' => ['status' => 'TS']]], 200)]);

        $result = (new AirtelMoneyDriver())->verify($this->integration(), 'AM123');

        $this->assertTrue($result['success']);
    }

    public function test_refund_reports_success(): void
    {
        $this->fakeToken();
        Http::fake(['openapi.airtel.africa/standard/v1/payments/refund' => Http::response(['status' => ['success' => true]], 200)]);

        $result = (new AirtelMoneyDriver())->refund($this->integration(), 'AM123');

        $this->assertTrue($result['success']);
    }

    public function test_test_reports_success_when_a_token_is_issued(): void
    {
        $this->fakeToken();

        $result = (new AirtelMoneyDriver())->test($this->integration());

        $this->assertTrue($result['success']);
    }
}
