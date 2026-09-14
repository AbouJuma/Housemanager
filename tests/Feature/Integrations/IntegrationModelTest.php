<?php

namespace Tests\Feature\Integrations;

use App\Modules\Companies\Models\Company;
use App\Modules\Integrations\Models\Integration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntegrationModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_credentials_round_trip_through_the_encrypted_cast(): void
    {
        $company = Company::factory()->create();

        $integration = Integration::create([
            'company_id' => $company->id,
            'type' => 'payment',
            'provider' => 'stripe',
            'credentials' => ['api_key' => 'pk_test_123', 'api_secret' => 'sk_test_456'],
        ]);

        $this->assertSame('pk_test_123', $integration->fresh()->credential('api_key'));
        $this->assertSame('sk_test_456', $integration->fresh()->credential('api_secret'));
    }

    public function test_the_credentials_column_is_never_stored_in_plaintext(): void
    {
        $company = Company::factory()->create();

        Integration::create([
            'company_id' => $company->id,
            'type' => 'payment',
            'provider' => 'stripe',
            'credentials' => ['api_key' => 'super-secret-value'],
        ]);

        $raw = \Illuminate\Support\Facades\DB::table('integrations')->first();

        $this->assertStringNotContainsString('super-secret-value', $raw->credentials);
    }

    public function test_credential_returns_null_for_a_missing_key(): void
    {
        $company = Company::factory()->create();
        $integration = Integration::create([
            'company_id' => $company->id,
            'type' => 'payment',
            'provider' => 'stripe',
            'credentials' => ['api_key' => 'pk_test_123'],
        ]);

        $this->assertNull($integration->credential('api_secret'));
    }

    public function test_is_configured_reflects_whether_the_row_was_persisted(): void
    {
        $company = Company::factory()->create();

        $unsaved = new Integration(['company_id' => $company->id, 'type' => 'payment', 'provider' => 'stripe']);
        $this->assertFalse($unsaved->isConfigured());

        $saved = Integration::create(['company_id' => $company->id, 'type' => 'payment', 'provider' => 'paypal']);
        $this->assertTrue($saved->isConfigured());
    }
}
