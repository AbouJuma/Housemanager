<?php

namespace Tests\Feature\Companies;

use App\Modules\Companies\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CompanyModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_uuid_is_generated_on_creation(): void
    {
        $company = Company::factory()->create();

        $this->assertTrue(Str::isUuid($company->uuid));
    }

    public function test_the_route_key_name_is_uuid(): void
    {
        $this->assertSame('uuid', (new Company())->getRouteKeyName());
    }

    public function test_is_active_reflects_status(): void
    {
        $active = Company::factory()->active()->create();
        $trial = Company::factory()->create();

        $this->assertTrue($active->isActive());
        $this->assertFalse($trial->isActive());
    }

    public function test_is_on_trial_checks_status_and_expiry(): void
    {
        $onTrial = Company::factory()->create(['trial_ends_at' => now()->addDay()]);
        $expired = Company::factory()->create(['trial_ends_at' => now()->subDay()]);
        $active = Company::factory()->active()->create();

        $this->assertTrue($onTrial->isOnTrial());
        $this->assertFalse($expired->isOnTrial());
        $this->assertFalse($active->isOnTrial());
    }

    public function test_logo_url_is_null_without_media(): void
    {
        $company = Company::factory()->create();

        $this->assertNull($company->logoUrl());
    }
}
