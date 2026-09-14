<?php

namespace Tests\Feature\Maintenance;

use App\Modules\Companies\Models\Company;
use App\Modules\Maintenance\Models\MaintenanceRequest;
use App\Modules\Properties\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MaintenanceRequestModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_uuid_is_generated_on_creation(): void
    {
        $company = Company::factory()->create();
        $property = Property::factory()->create(['company_id' => $company->id]);
        $request = MaintenanceRequest::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);

        $this->assertTrue(Str::isUuid($request->uuid));
    }

    public function test_the_route_key_name_is_uuid(): void
    {
        $this->assertSame('uuid', (new MaintenanceRequest())->getRouteKeyName());
    }

    public function test_is_completed_reflects_status(): void
    {
        $company = Company::factory()->create();
        $property = Property::factory()->create(['company_id' => $company->id]);
        $open = MaintenanceRequest::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);
        $completed = MaintenanceRequest::factory()->completed()->create(['company_id' => $company->id, 'property_id' => $property->id]);

        $this->assertFalse($open->isCompleted());
        $this->assertTrue($completed->isCompleted());
    }

    public function test_has_recorded_expense_reflects_expense_id(): void
    {
        $company = Company::factory()->create();
        $property = Property::factory()->create(['company_id' => $company->id]);
        $request = MaintenanceRequest::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);

        $this->assertFalse($request->hasRecordedExpense());
    }
}
