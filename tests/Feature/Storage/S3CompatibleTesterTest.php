<?php

namespace Tests\Feature\Storage;

use App\Modules\Companies\Models\Company;
use App\Modules\Integrations\Models\Integration;
use App\Modules\Storage\Services\StorageResolver;
use App\Modules\Storage\Testers\S3CompatibleTester;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class S3CompatibleTesterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reports_success_when_the_resolved_disk_write_and_delete_succeed(): void
    {
        Storage::fake('public');
        $company = Company::factory()->create();
        $integration = Integration::create(['company_id' => $company->id, 'type' => 'storage', 'provider' => 's3']);

        $resolver = Mockery::mock(StorageResolver::class);
        $resolver->shouldReceive('diskForIntegration')->once()->with($integration)->andReturn('public');

        $result = (new S3CompatibleTester($resolver))->test($integration);

        $this->assertTrue($result['success']);
    }

    public function test_it_reports_failure_when_the_resolver_cannot_build_a_disk(): void
    {
        $company = Company::factory()->create();
        $integration = Integration::create(['company_id' => $company->id, 'type' => 'storage', 'provider' => 's3']);

        $resolver = Mockery::mock(StorageResolver::class);
        $resolver->shouldReceive('diskForIntegration')->once()->with($integration)->andThrow(new RuntimeException('bad credentials'));

        $result = (new S3CompatibleTester($resolver))->test($integration);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('bad credentials', $result['message']);
    }

    public function test_it_delegates_to_the_real_resolver_regardless_of_is_active(): void
    {
        $company = Company::factory()->create();
        $integration = Integration::create(['company_id' => $company->id, 'type' => 'storage', 'provider' => 's3', 'is_active' => false]);

        $resolver = Mockery::mock(StorageResolver::class);
        // Proves the tester doesn't add its own is_active gate on top of
        // the resolver's - StorageResolverTest covers that the *real*
        // diskForIntegration() ignores is_active.
        $resolver->shouldReceive('diskForIntegration')->once()->with($integration)->andReturn('public');
        Storage::fake('public');

        $result = (new S3CompatibleTester($resolver))->test($integration);

        $this->assertTrue($result['success']);
    }
}
