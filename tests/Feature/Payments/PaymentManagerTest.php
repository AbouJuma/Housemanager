<?php

namespace Tests\Feature\Payments;

use App\Modules\Companies\Models\Company;
use App\Modules\Integrations\Models\Integration;
use App\Modules\Payments\Drivers\SelcomDriver;
use App\Modules\Payments\Drivers\StripeDriver;
use App\Modules\Payments\Services\PaymentManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentManagerTest extends TestCase
{
    use RefreshDatabase;

    protected PaymentManager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = app(PaymentManager::class);
    }

    public function test_charge_reports_failure_when_no_provider_is_active(): void
    {
        $company = Company::factory()->create();

        $result = $this->manager->charge($company, 5000, 'usd', 'order-1');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('No active payment provider', $result['message']);
    }

    public function test_charge_ignores_an_inactive_integration(): void
    {
        $company = Company::factory()->create();
        Integration::create([
            'company_id' => $company->id, 'type' => 'payment', 'provider' => 'stripe', 'is_active' => false,
            'credentials' => ['api_secret' => 'sk_test_xxx'],
        ]);

        $result = $this->manager->charge($company, 5000, 'usd', 'order-1');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('No active payment provider', $result['message']);
    }

    public function test_charge_delegates_to_the_active_providers_driver(): void
    {
        Http::fake(['api.stripe.com/*' => Http::response(['id' => 'cs_1', 'url' => 'https://checkout.stripe.com/pay/cs_1'], 200)]);

        $company = Company::factory()->create();
        Integration::create([
            'company_id' => $company->id, 'type' => 'payment', 'provider' => 'stripe', 'is_active' => true,
            'credentials' => ['api_secret' => 'sk_test_xxx'],
        ]);

        $result = $this->manager->charge($company, 5000, 'usd', 'order-1');

        $this->assertTrue($result['success']);
        $this->assertSame('cs_1', $result['reference']);
    }

    public function test_verify_targets_a_specific_provider_regardless_of_which_is_currently_active(): void
    {
        Http::fake(['api.stripe.com/*' => Http::response(['payment_status' => 'paid'], 200)]);

        $company = Company::factory()->create();
        // stripe is NOT active - paypal is - but verify() should still
        // reach stripe because that's the provider a prior charge used.
        Integration::create([
            'company_id' => $company->id, 'type' => 'payment', 'provider' => 'stripe', 'is_active' => false,
            'credentials' => ['api_secret' => 'sk_test_xxx'],
        ]);
        Integration::create([
            'company_id' => $company->id, 'type' => 'payment', 'provider' => 'paypal', 'is_active' => true,
            'credentials' => ['api_key' => 'id', 'api_secret' => 'secret'],
        ]);

        $result = $this->manager->verify($company, 'stripe', 'cs_1');

        $this->assertTrue($result['success']);
    }

    public function test_verify_reports_failure_when_that_provider_was_never_configured(): void
    {
        $company = Company::factory()->create();

        $result = $this->manager->verify($company, 'stripe', 'cs_1');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('No stripe integration', $result['message']);
    }

    public function test_driver_for_resolves_every_catalog_provider(): void
    {
        foreach (array_keys(config('integrations.payment_drivers')) as $provider) {
            $integration = Integration::make(['type' => 'payment', 'provider' => $provider]);

            $this->assertNotNull($this->manager->driverFor($integration), "No driver resolved for [{$provider}]");
        }
    }

    public function test_driver_for_resolves_the_correct_driver_class_per_provider(): void
    {
        $this->assertInstanceOf(StripeDriver::class, $this->manager->driverFor(Integration::make(['type' => 'payment', 'provider' => 'stripe'])));
        $this->assertInstanceOf(SelcomDriver::class, $this->manager->driverFor(Integration::make(['type' => 'payment', 'provider' => 'selcom'])));
    }
}
