<?php

namespace Tests\Feature\Storage;

use App\Modules\Companies\Models\Company;
use App\Modules\Integrations\Models\Integration;
use App\Modules\Storage\Services\StorageResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class StorageResolverTest extends TestCase
{
    use RefreshDatabase;

    protected StorageResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = app(StorageResolver::class);
    }

    public function test_a_company_with_no_storage_integration_uses_the_public_disk(): void
    {
        $company = Company::factory()->create();

        $this->assertSame('public', $this->resolver->diskFor($company));
    }

    public function test_a_company_with_an_active_local_integration_uses_the_public_disk(): void
    {
        $company = Company::factory()->create();
        Integration::create(['company_id' => $company->id, 'type' => 'storage', 'provider' => 'local', 'is_active' => true]);

        $this->assertSame('public', $this->resolver->diskFor($company));
    }

    public function test_an_inactive_integration_is_ignored_in_favour_of_the_public_disk(): void
    {
        $company = Company::factory()->create();
        Integration::create(['company_id' => $company->id, 'type' => 'storage', 'provider' => 's3', 'is_active' => false]);

        $this->assertSame('public', $this->resolver->diskFor($company));
    }

    public function test_an_active_s3_integration_registers_and_returns_the_company_disk(): void
    {
        $company = Company::factory()->create();
        Integration::create([
            'company_id' => $company->id,
            'type' => 'storage',
            'provider' => 's3',
            'is_active' => true,
            'credentials' => [
                'api_key' => 'AKIA123',
                'api_secret' => 'secret123',
                'merchant_id' => 'my-bucket',
                'username' => 'us-east-1',
            ],
        ]);

        $disk = $this->resolver->diskFor($company);

        $this->assertSame('company', $disk);
        $config = config('filesystems.disks.company');
        $this->assertSame('s3', $config['driver']);
        $this->assertSame('AKIA123', $config['key']);
        $this->assertSame('secret123', $config['secret']);
        $this->assertSame('my-bucket', $config['bucket']);
        $this->assertSame('us-east-1', $config['region']);
        $this->assertArrayNotHasKey('endpoint', $config);
    }

    public function test_r2_credentials_include_a_custom_endpoint_when_the_password_field_is_set(): void
    {
        $company = Company::factory()->create();
        $integration = Integration::create([
            'company_id' => $company->id,
            'type' => 'storage',
            'provider' => 'r2',
            'is_active' => true,
            'credentials' => [
                'api_key' => 'key',
                'api_secret' => 'secret',
                'merchant_id' => 'bucket',
                'password' => 'https://accountid.r2.cloudflarestorage.com',
            ],
        ]);

        $this->resolver->diskFor($integration->company);

        $config = config('filesystems.disks.company');
        $this->assertSame('https://accountid.r2.cloudflarestorage.com', $config['endpoint']);
        $this->assertTrue($config['use_path_style_endpoint']);
    }

    public function test_a_gcs_integration_registers_and_returns_the_company_disk(): void
    {
        $company = Company::factory()->create();
        Integration::create([
            'company_id' => $company->id,
            'type' => 'storage',
            'provider' => 'gcs',
            'is_active' => true,
            'credentials' => [
                'api_key' => 'GOOG123',
                'api_secret' => 'secret123',
                'merchant_id' => 'my-gcs-bucket',
                'username' => 'auto',
            ],
        ]);

        $disk = $this->resolver->diskFor($company);

        $this->assertSame('company', $disk);
        $config = config('filesystems.disks.company');
        $this->assertSame('s3', $config['driver']);
        $this->assertSame('GOOG123', $config['key']);
        $this->assertSame('secret123', $config['secret']);
        $this->assertSame('my-gcs-bucket', $config['bucket']);
        $this->assertSame('auto', $config['region']);
    }

    public function test_disk_for_integration_resolves_credentials_even_when_the_integration_is_not_active(): void
    {
        $company = Company::factory()->create();
        $integration = Integration::create([
            'company_id' => $company->id,
            'type' => 'storage',
            'provider' => 's3',
            'is_active' => false,
            'credentials' => ['api_key' => 'k', 'api_secret' => 's', 'merchant_id' => 'b'],
        ]);

        $this->assertSame('company', $this->resolver->diskForIntegration($integration));
    }
}
