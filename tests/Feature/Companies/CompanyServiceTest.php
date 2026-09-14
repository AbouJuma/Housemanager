<?php

namespace Tests\Feature\Companies;

use App\Modules\Companies\Models\Company;
use App\Modules\Companies\Repositories\CompanyRepositoryInterface;
use App\Modules\Companies\Repositories\EloquentCompanyRepository;
use App\Modules\Companies\Services\CompanyService;
use App\Modules\Integrations\Models\Integration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class CompanyServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CompanyService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(CompanyService::class);
    }

    public function test_the_repository_interface_resolves_to_the_eloquent_implementation(): void
    {
        $this->assertInstanceOf(EloquentCompanyRepository::class, app(CompanyRepositoryInterface::class));
    }

    public function test_create_generates_a_slug_from_the_name_when_none_given(): void
    {
        $company = $this->service->create(['name' => 'Acme Property Group']);

        $this->assertSame('acme-property-group', $company->slug);
    }

    public function test_create_deduplicates_a_colliding_slug(): void
    {
        Company::factory()->create(['slug' => 'acme']);

        $company = $this->service->create(['name' => 'Acme', 'slug' => 'acme']);

        $this->assertSame('acme-1', $company->slug);
    }

    public function test_create_defaults_status_to_a_14_day_trial(): void
    {
        $company = $this->service->create(['name' => 'Acme']);

        $this->assertSame('trial', $company->status);
        $this->assertTrue($company->trial_ends_at->between(now()->addDays(13), now()->addDays(15)));
    }

    public function test_update_only_regenerates_the_slug_when_it_changed(): void
    {
        $company = Company::factory()->create(['slug' => 'acme']);

        $unchanged = $this->service->update($company, ['name' => 'Acme Renamed', 'slug' => 'acme']);
        $this->assertSame('acme', $unchanged->slug);

        $changed = $this->service->update($unchanged, ['name' => 'Acme Renamed', 'slug' => 'new-acme']);
        $this->assertSame('new-acme', $changed->slug);
    }

    public function test_update_branding_stores_a_logo_and_colors(): void
    {
        Storage::fake('public');
        $company = Company::factory()->create();
        $logo = UploadedFile::fake()->image('logo.png');

        $updated = $this->service->updateBranding($company, $logo, '#111111', '#222222');

        $this->assertSame('#111111', $updated->primary_color);
        $this->assertSame('#222222', $updated->secondary_color);
        $this->assertNotNull($updated->logoUrl());
    }

    public function test_update_branding_without_a_logo_only_touches_colors(): void
    {
        $company = Company::factory()->create();

        $updated = $this->service->updateBranding($company, null, '#333333', null);

        $this->assertSame('#333333', $updated->primary_color);
        $this->assertNull($updated->logoUrl());
    }

    public function test_update_branding_consults_the_companys_active_storage_integration(): void
    {
        $company = Company::factory()->create();
        Integration::create([
            'company_id' => $company->id,
            'type' => 'storage',
            'provider' => 'gcs',
            'is_active' => true,
        ]);
        $logo = UploadedFile::fake()->image('logo.png');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/gcs.*wired up/');

        $this->service->updateBranding($company, $logo, null, null);
    }
}
