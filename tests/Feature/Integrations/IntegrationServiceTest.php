<?php

namespace Tests\Feature\Integrations;

use App\Modules\Companies\Models\Company;
use App\Modules\Integrations\Models\Integration;
use App\Modules\Integrations\Services\IntegrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntegrationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected IntegrationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(IntegrationService::class);
    }

    public function test_find_or_new_for_an_unknown_provider_throws(): void
    {
        $company = Company::factory()->create();

        $this->expectException(\InvalidArgumentException::class);

        $this->service->findOrNewFor($company->id, 'payment', 'not-a-real-gateway');
    }

    public function test_find_or_new_for_a_known_provider_with_no_row_yet_returns_an_unsaved_instance(): void
    {
        $company = Company::factory()->create();

        $integration = $this->service->findOrNewFor($company->id, 'payment', 'stripe');

        $this->assertFalse($integration->exists);
        $this->assertSame('payment', $integration->type);
        $this->assertSame('stripe', $integration->provider);
    }

    public function test_saving_a_new_integration_stores_credentials_and_marks_it_untested(): void
    {
        $company = Company::factory()->create();
        $integration = $this->service->findOrNewFor($company->id, 'payment', 'stripe');

        $saved = $this->service->save($integration, [
            'mode' => 'sandbox',
            'is_active' => true,
            'api_key' => 'pk_test_123',
        ]);

        $this->assertTrue($saved->exists);
        $this->assertSame('untested', $saved->status);
        $this->assertTrue($saved->is_active);
        $this->assertSame('pk_test_123', $saved->credential('api_key'));
    }

    public function test_saving_with_a_blank_credential_field_keeps_the_existing_value(): void
    {
        $company = Company::factory()->create();
        $integration = $this->service->findOrNewFor($company->id, 'payment', 'stripe');
        $this->service->save($integration, ['mode' => 'sandbox', 'api_key' => 'pk_test_original', 'api_secret' => 'sk_test_original']);

        // Re-saving with api_key blank (the form's "leave unchanged" convention).
        $resaved = $this->service->save($integration->fresh(), ['mode' => 'live', 'api_key' => '', 'api_secret' => 'sk_test_updated']);

        $this->assertSame('pk_test_original', $resaved->credential('api_key'));
        $this->assertSame('sk_test_updated', $resaved->credential('api_secret'));
        $this->assertSame('live', $resaved->mode);
    }

    public function test_testing_a_provider_with_no_tester_registered_reports_that_honestly(): void
    {
        $company = Company::factory()->create();
        // 'maps'/'google_maps' is genuinely untested-yet - the Maps
        // module (Phase E) hasn't been built. ('stripe' was originally
        // used here in Module 7, then 'selcom' after Module 11 gave
        // Stripe a real tester - by the end of Module 11 every Storage/
        // Email/SMS/Payment provider has one, so this test moved to an
        // entirely unbuilt type rather than an individual provider,
        // which would otherwise make an actual unfaked HTTP call here
        // the moment that provider's module lands.)
        $integration = Integration::create(['company_id' => $company->id, 'type' => 'maps', 'provider' => 'google_maps']);

        $result = $this->service->test($integration);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('No connection tester', $result['message']);
        $this->assertSame('untested', $integration->fresh()->status);
        $this->assertNotNull($integration->fresh()->last_tested_at);
    }

    public function test_testing_a_provider_with_a_registered_tester_uses_it(): void
    {
        config(['integrations.testers.stripe' => FakeSuccessfulTester::class]);

        $company = Company::factory()->create();
        $integration = Integration::create(['company_id' => $company->id, 'type' => 'payment', 'provider' => 'stripe']);

        $result = $this->service->test($integration);

        $this->assertTrue($result['success']);
        $this->assertSame('connected', $integration->fresh()->status);
    }

    public function test_a_failed_test_marks_the_status_failed(): void
    {
        config(['integrations.testers.stripe' => FakeFailingTester::class]);

        $company = Company::factory()->create();
        $integration = Integration::create(['company_id' => $company->id, 'type' => 'payment', 'provider' => 'stripe']);

        $result = $this->service->test($integration);

        $this->assertFalse($result['success']);
        $this->assertSame('failed', $integration->fresh()->status);
    }
}

class FakeSuccessfulTester implements \App\Modules\Integrations\Contracts\IntegrationTester
{
    public function test(Integration $integration): array
    {
        return ['success' => true, 'message' => 'Connected.'];
    }
}

class FakeFailingTester implements \App\Modules\Integrations\Contracts\IntegrationTester
{
    public function test(Integration $integration): array
    {
        return ['success' => false, 'message' => 'Invalid credentials.'];
    }
}
