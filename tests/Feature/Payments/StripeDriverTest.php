<?php

namespace Tests\Feature\Payments;

use App\Modules\Companies\Models\Company;
use App\Modules\Integrations\Models\Integration;
use App\Modules\Payments\Drivers\StripeDriver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StripeDriverTest extends TestCase
{
    use RefreshDatabase;

    protected function integration(): Integration
    {
        $company = Company::factory()->create();

        return Integration::create([
            'company_id' => $company->id,
            'type' => 'payment',
            'provider' => 'stripe',
            'credentials' => ['api_secret' => 'sk_test_xxx'],
        ]);
    }

    public function test_charge_creates_a_checkout_session_and_returns_a_redirect_url(): void
    {
        Http::fake(['api.stripe.com/v1/checkout/sessions' => Http::response([
            'id' => 'cs_test_123', 'url' => 'https://checkout.stripe.com/pay/cs_test_123',
        ], 200)]);

        $result = (new StripeDriver())->charge($this->integration(), 5000, 'usd', 'order-1');

        $this->assertTrue($result['success']);
        $this->assertSame('cs_test_123', $result['reference']);
        $this->assertSame('https://checkout.stripe.com/pay/cs_test_123', $result['redirect_url']);
        Http::assertSent(fn ($request) => $request->hasHeader('Idempotency-Key', 'order-1')
            && $request['line_items'][0]['price_data']['unit_amount'] === 5000);
    }

    public function test_charge_reports_stripes_error_message_on_failure(): void
    {
        Http::fake(['api.stripe.com/*' => Http::response(['error' => ['message' => 'Invalid API Key provided']], 401)]);

        $result = (new StripeDriver())->charge($this->integration(), 5000, 'usd', 'order-1');

        $this->assertFalse($result['success']);
        $this->assertSame('Invalid API Key provided', $result['message']);
    }

    public function test_verify_reports_success_when_payment_status_is_paid(): void
    {
        Http::fake(['api.stripe.com/*' => Http::response(['payment_status' => 'paid'], 200)]);

        $result = (new StripeDriver())->verify($this->integration(), 'cs_test_123');

        $this->assertTrue($result['success']);
    }

    public function test_verify_reports_not_successful_when_unpaid(): void
    {
        Http::fake(['api.stripe.com/*' => Http::response(['payment_status' => 'unpaid'], 200)]);

        $result = (new StripeDriver())->verify($this->integration(), 'cs_test_123');

        $this->assertFalse($result['success']);
    }

    public function test_refund_sends_the_payment_intent_and_optional_amount(): void
    {
        Http::fake(['api.stripe.com/v1/refunds' => Http::response(['id' => 're_123'], 200)]);

        $result = (new StripeDriver())->refund($this->integration(), 'pi_123', 2500);

        $this->assertTrue($result['success']);
        Http::assertSent(fn ($request) => $request['payment_intent'] === 'pi_123' && $request['amount'] === 2500);
    }

    public function test_test_reports_success_when_the_balance_endpoint_responds(): void
    {
        Http::fake(['api.stripe.com/v1/balance' => Http::response(['object' => 'balance'], 200)]);

        $result = (new StripeDriver())->test($this->integration());

        $this->assertTrue($result['success']);
    }

    public function test_test_reports_failure_when_credentials_are_rejected(): void
    {
        Http::fake(['api.stripe.com/*' => Http::response(['error' => ['message' => 'Invalid API Key provided']], 401)]);

        $result = (new StripeDriver())->test($this->integration());

        $this->assertFalse($result['success']);
        $this->assertSame('Invalid API Key provided', $result['message']);
    }
}
