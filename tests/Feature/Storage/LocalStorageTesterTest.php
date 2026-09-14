<?php

namespace Tests\Feature\Storage;

use App\Modules\Companies\Models\Company;
use App\Modules\Integrations\Models\Integration;
use App\Modules\Storage\Testers\LocalStorageTester;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class LocalStorageTesterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reports_success_when_the_public_disk_is_writable(): void
    {
        Storage::fake('public');
        $company = Company::factory()->create();
        $integration = Integration::create(['company_id' => $company->id, 'type' => 'storage', 'provider' => 'local']);

        $result = (new LocalStorageTester())->test($integration);

        $this->assertTrue($result['success']);
    }

    public function test_it_reports_failure_when_writing_fails(): void
    {
        $company = Company::factory()->create();
        $integration = Integration::create(['company_id' => $company->id, 'type' => 'storage', 'provider' => 'local']);

        $disk = Mockery::mock();
        $disk->shouldReceive('put')->andThrow(new RuntimeException('permission denied'));
        $disk->shouldReceive('delete')->andReturnTrue();
        Storage::shouldReceive('disk')->with('public')->andReturn($disk);

        $result = (new LocalStorageTester())->test($integration);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('permission denied', $result['message']);
    }
}
