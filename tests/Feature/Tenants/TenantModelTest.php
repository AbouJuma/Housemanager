<?php

namespace Tests\Feature\Tenants;

use App\Modules\Companies\Models\Company;
use App\Modules\Tenants\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class TenantModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_uuid_is_generated_on_creation(): void
    {
        $tenant = Tenant::factory()->create(['company_id' => Company::factory()->create()->id]);

        $this->assertTrue(Str::isUuid($tenant->uuid));
    }

    public function test_the_route_key_name_is_uuid(): void
    {
        $this->assertSame('uuid', (new Tenant())->getRouteKeyName());
    }

    public function test_is_blacklisted_reflects_status(): void
    {
        $company = Company::factory()->create();
        $blacklisted = Tenant::factory()->blacklisted()->create(['company_id' => $company->id]);
        $active = Tenant::factory()->create(['company_id' => $company->id]);

        $this->assertTrue($blacklisted->isBlacklisted());
        $this->assertFalse($active->isBlacklisted());
    }

    public function test_identification_number_round_trips_encrypted(): void
    {
        $tenant = Tenant::factory()->create([
            'company_id' => Company::factory()->create()->id,
            'identification_number' => 'ID-12345678',
        ]);

        $this->assertSame('ID-12345678', $tenant->fresh()->identification_number);
    }

    public function test_identification_number_is_stored_encrypted_at_rest(): void
    {
        $tenant = Tenant::factory()->create([
            'company_id' => Company::factory()->create()->id,
            'identification_number' => 'super-secret-id-number',
        ]);

        $raw = DB::table('tenants')->where('id', $tenant->id)->value('identification_number');

        $this->assertStringNotContainsString('super-secret-id-number', $raw);
    }

    public function test_photo_url_is_null_without_media(): void
    {
        $tenant = Tenant::factory()->create(['company_id' => Company::factory()->create()->id]);

        $this->assertNull($tenant->photoUrl());
    }
}
