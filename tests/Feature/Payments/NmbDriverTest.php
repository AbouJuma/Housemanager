<?php

namespace Tests\Feature\Payments;

use App\Modules\Companies\Models\Company;
use App\Modules\Integrations\Models\Integration;
use App\Modules\Payments\Drivers\NmbDriver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NmbDriverTest extends TestCase
{
    use RefreshDatabase;

    protected function integration(?string $baseUrl = 'https://example-nmb-gateway.test'): Integration
    {
        $company = Company::factory()->create();

        return Integration::create([
            'company_id' => $company->id,
            'type' => 'payment',
            'provider' => 'nmb',
            'credentials' => array_filter([
                'api_key' => 'client-id',
                'api_secret' => 'client-secret',
                'merchant_id' => 'M001',
                'password' => $baseUrl,
            ], fn ($v) => $v !== null),
        ]);
    }

    public function test_charge_without_a_base_url_override_fails_without_any_network_call(): void
    {
        Http::fake();

        $result = (new NmbDriver())->charge($this->integration(null), 5000, 'tzs', 'order-1');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Base URL Override', $result['message']);
        Http::assertNothingSent();
    }

    public function test_charge_with_a_base_url_requests_a_checkout_and_reports_success(): void
    {
        Http::fake([
            'example-nmb-gateway.test/oauth/token' => Http::response(['access_token' => 'token-123'], 200),
            'example-nmb-gateway.test/collections/v1/checkout' => Http::response(['status' => 'ACCEPTED', 'transactionId' => 'N1'], 200),
        ]);

        $result = (new NmbDriver())->charge($this->integration(), 5000, 'tzs', 'order-1', ['phone' => '255700000000']);

        $this->assertTrue($result['success']);
        $this->assertSame('N1', $result['reference']);
    }

    public function test_verify_reports_success_when_status_is_completed(): void
    {
        Http::fake([
            'example-nmb-gateway.test/oauth/token' => Http::response(['access_token' => 'token-123'], 200),
            'example-nmb-gateway.test/collections/v1/checkout/*' => Http::response(['status' => 'COMPLETED'], 200),
        ]);

        $result = (new NmbDriver())->verify($this->integration(), 'N1');

        $this->assertTrue($result['success']);
    }

    public function test_refund_reports_that_it_is_not_supported(): void
    {
        Http::fake();

        $result = (new NmbDriver())->refund($this->integration(), 'N1');

        $this->assertFalse($result['success']);
        Http::assertNothingSent();
    }

    public function test_test_reports_success_when_a_token_is_issued(): void
    {
        Http::fake(['example-nmb-gateway.test/oauth/token' => Http::response(['access_token' => 'token-123'], 200)]);

        $result = (new NmbDriver())->test($this->integration());

        $this->assertTrue($result['success']);
    }
}
