<?php

namespace Tests\Feature\VisitorManagement;

use App\Modules\Companies\Models\Company;
use App\Modules\Properties\Models\Property;
use App\Modules\Tenants\Models\Tenant;
use App\Modules\VisitorManagement\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class VisitorModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_uuid_is_generated_on_creation(): void
    {
        $company = Company::factory()->create();
        $property = Property::factory()->create(['company_id' => $company->id]);
        $visitor = Visitor::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);

        $this->assertTrue(Str::isUuid($visitor->uuid));
    }

    public function test_the_route_key_name_is_uuid(): void
    {
        $this->assertSame('uuid', (new Visitor())->getRouteKeyName());
    }

    public function test_is_checked_in_reflects_check_out_at(): void
    {
        $company = Company::factory()->create();
        $property = Property::factory()->create(['company_id' => $company->id]);
        $stillIn = Visitor::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);
        $checkedOut = Visitor::factory()->checkedOut()->create(['company_id' => $company->id, 'property_id' => $property->id]);

        $this->assertTrue($stillIn->isCheckedIn());
        $this->assertFalse($checkedOut->isCheckedIn());
    }

    public function test_host_display_name_prefers_the_tenant_over_free_text(): void
    {
        $company = Company::factory()->create();
        $property = Property::factory()->create(['company_id' => $company->id]);
        $tenant = Tenant::factory()->create(['company_id' => $company->id, 'name' => 'Jane Tenant']);

        $withTenant = Visitor::factory()->create([
            'company_id' => $company->id,
            'property_id' => $property->id,
            'tenant_id' => $tenant->id,
            'host_name' => 'Should Be Ignored',
        ]);
        $withHostNameOnly = Visitor::factory()->create([
            'company_id' => $company->id,
            'property_id' => $property->id,
            'host_name' => 'Building Management',
        ]);
        $withNeither = Visitor::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);

        $this->assertSame('Jane Tenant', $withTenant->hostDisplayName());
        $this->assertSame('Building Management', $withHostNameOnly->hostDisplayName());
        $this->assertNull($withNeither->hostDisplayName());
    }
}
