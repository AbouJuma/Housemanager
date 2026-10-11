<?php

namespace Tests\Feature\LandlordPortal;

use App\Modules\Companies\Models\Company;
use App\Modules\Landlords\Models\Landlord;
use App\Modules\Leases\Models\Lease;
use App\Modules\Properties\Models\Building;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use App\Modules\Sales\Models\Buyer;
use App\Modules\Sales\Services\SaleService;
use App\Modules\Tenants\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The mobile app's landlord property-detail screen (and, going forward,
 * anywhere else that needs unit-level detail) reads this endpoint's
 * `buildings` array - PropertyResource only includes it when the
 * controller explicitly eager-loads 'buildings.units', which the web
 * Blade view never needed since it reads $property->buildings directly.
 */
class LandlordPortalApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_property_show_includes_buildings_units_and_the_occupying_tenant(): void
    {
        $company = Company::factory()->create();
        $landlord = Landlord::factory()->create(['company_id' => $company->id]);
        $landlord->forceFill(['password' => 'secret123'])->save();
        $property = Property::factory()->create(['company_id' => $company->id, 'landlord_id' => $landlord->id]);
        $building = Building::factory()->create(['company_id' => $company->id, 'property_id' => $property->id, 'name' => 'Block A']);
        $occupiedUnit = Unit::factory()->create([
            'company_id' => $company->id,
            'building_id' => $building->id,
            'unit_number' => 'A1',
            'status' => 'occupied',
            'rent_amount' => 500,
        ]);
        $vacantUnit = Unit::factory()->create([
            'company_id' => $company->id,
            'building_id' => $building->id,
            'unit_number' => 'A2',
            'status' => 'vacant',
        ]);
        $tenant = Tenant::factory()->create(['company_id' => $company->id, 'name' => 'Amina Tenant', 'phone' => '0712345678']);
        Lease::factory()->create([
            'company_id' => $company->id,
            'tenant_id' => $tenant->id,
            'unit_id' => $occupiedUnit->id,
            'status' => 'active',
        ]);

        $login = $this->postJson('/api/v1/portal/landlord/login', ['email' => $landlord->email, 'password' => 'secret123']);
        $token = $login->json('token');

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/v1/portal/landlord/properties/{$property->uuid}");

        $response->assertOk();
        $response->assertJsonPath('data.buildings.0.name', 'Block A');

        $units = collect($response->json('data.buildings.0.units'));
        $occupied = $units->firstWhere('uuid', $occupiedUnit->uuid);
        $vacant = $units->firstWhere('uuid', $vacantUnit->uuid);

        $this->assertSame('Amina Tenant', $occupied['tenant']['name']);
        $this->assertSame('0712345678', $occupied['tenant']['phone']);
        $this->assertNull($vacant['tenant']);
    }

    /**
     * A landlord needs to see a sold unit's buyer and how much of the
     * purchase is still outstanding - including a partial payment, not
     * just "paid" vs "unpaid".
     */
    public function test_property_show_includes_the_buyer_and_a_partial_payment_for_a_sold_unit(): void
    {
        $company = Company::factory()->create();
        $landlord = Landlord::factory()->create(['company_id' => $company->id]);
        $landlord->forceFill(['password' => 'secret123'])->save();
        $property = Property::factory()->create(['company_id' => $company->id, 'landlord_id' => $landlord->id]);
        $building = Building::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);
        $unit = Unit::factory()->create(['company_id' => $company->id, 'building_id' => $building->id, 'unit_number' => 'B1']);
        $buyer = Buyer::factory()->create(['company_id' => $company->id, 'name' => 'Asha Buyer', 'phone' => '0788111222']);

        // A non-zero down payment is what SaleService::create() actually
        // checks to mark a sale 'active' (see its own docblock) -
        // currentSale() only picks up active/completed sales, matching
        // every other "who currently holds this unit" check in the app.
        $sale = app(SaleService::class)->create([
            'company_id' => $company->id,
            'unit_id' => $unit->id,
            'new_buyer_id' => $buyer->id,
            'sale_date' => now()->toDateString(),
            'total_price' => 10000,
            'down_payment' => 1000,
            'installment_period' => 3,
            'installment_amount' => 3000,
        ]);

        $login = $this->postJson('/api/v1/portal/landlord/login', ['email' => $landlord->email, 'password' => 'secret123']);
        $token = $login->json('token');

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/v1/portal/landlord/properties/{$property->uuid}");

        $response->assertOk();
        $units = collect($response->json('data.buildings.0.units'));
        $sold = $units->firstWhere('uuid', $unit->uuid);

        $this->assertSame('Asha Buyer', $sold['sale']['buyer']['name']);
        $this->assertSame('0788111222', $sold['sale']['buyer']['phone']);
        $this->assertEquals(1000, $sold['sale']['total_paid']);
        $this->assertEquals(9000, $sold['sale']['remaining_balance']);
        $this->assertNull($sold['tenant']);
    }

    public function test_a_landlord_cannot_see_buildings_for_another_landlords_property(): void
    {
        $company = Company::factory()->create();
        $landlord = Landlord::factory()->create(['company_id' => $company->id]);
        $landlord->forceFill(['password' => 'secret123'])->save();
        $otherProperty = Property::factory()->create(['company_id' => $company->id, 'landlord_id' => Landlord::factory()->create(['company_id' => $company->id])->id]);

        $login = $this->postJson('/api/v1/portal/landlord/login', ['email' => $landlord->email, 'password' => 'secret123']);
        $token = $login->json('token');

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/v1/portal/landlord/properties/{$otherProperty->uuid}")
            ->assertForbidden();
    }

    public function test_property_list_does_not_include_buildings_since_the_controller_never_eager_loads_them(): void
    {
        $company = Company::factory()->create();
        $landlord = Landlord::factory()->create(['company_id' => $company->id]);
        $landlord->forceFill(['password' => 'secret123'])->save();
        Property::factory()->create(['company_id' => $company->id, 'landlord_id' => $landlord->id]);

        $login = $this->postJson('/api/v1/portal/landlord/login', ['email' => $landlord->email, 'password' => 'secret123']);
        $token = $login->json('token');

        $response = $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/portal/landlord/properties');

        $response->assertOk();
        $this->assertArrayNotHasKey('buildings', $response->json('data.0'));
    }
}
