<?php

namespace Tests\Feature\LandlordPortal;

use App\Modules\Accounting\Models\Account;
use App\Modules\Companies\Models\Company;
use App\Modules\Documents\Models\Document;
use App\Modules\Expenses\Models\Expense;
use App\Modules\Landlords\Models\Landlord;
use App\Modules\Maintenance\Models\MaintenanceRequest;
use App\Modules\Properties\Models\Building;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandlordPortalSelfServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function makePortalLandlord(Company $company): Landlord
    {
        $landlord = Landlord::factory()->create(['company_id' => $company->id, 'email' => 'landlord@example.test']);
        $landlord->forceFill(['password' => 'correct-password'])->save();

        return $landlord;
    }

    protected function makeProperty(Company $company, Landlord $landlord, array $unitOverrides = []): Property
    {
        $property = Property::factory()->create(['company_id' => $company->id, 'landlord_id' => $landlord->id]);
        $building = Building::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);
        Unit::factory()->create(array_merge(['company_id' => $company->id, 'building_id' => $building->id], $unitOverrides));

        return $property;
    }

    public function test_dashboard_shows_only_the_authenticated_landlords_own_properties(): void
    {
        $company = Company::factory()->create();
        $landlord = $this->makePortalLandlord($company);
        $this->makeProperty($company, $landlord);

        $response = $this->actingAs($landlord, 'landlord')->get(route('landlord-portal.dashboard'));

        $response->assertOk();
        $response->assertViewHas('properties', fn ($properties) => $properties->count() === 1);
    }

    public function test_a_landlord_can_view_their_own_property_but_not_anothers(): void
    {
        $company = Company::factory()->create();
        $landlord = $this->makePortalLandlord($company);
        $ownProperty = $this->makeProperty($company, $landlord);

        $otherLandlord = Landlord::factory()->create(['company_id' => $company->id]);
        $otherProperty = $this->makeProperty($company, $otherLandlord);

        $this->actingAs($landlord, 'landlord')->get(route('landlord-portal.properties.show', $ownProperty))->assertOk();
        $this->actingAs($landlord, 'landlord')->get(route('landlord-portal.properties.show', $otherProperty))->assertForbidden();
    }

    public function test_expenses_index_shows_only_expenses_against_the_landlords_own_properties(): void
    {
        $company = Company::factory()->create();
        $landlord = $this->makePortalLandlord($company);
        $ownProperty = $this->makeProperty($company, $landlord);
        $account = Account::factory()->create(['company_id' => $company->id, 'type' => 'expense']);
        Expense::factory()->create([
            'company_id' => $company->id,
            'property_id' => $ownProperty->id,
            'account_id' => $account->id,
            'description' => 'Own Property Expense',
        ]);

        $otherLandlord = Landlord::factory()->create(['company_id' => $company->id]);
        $otherProperty = $this->makeProperty($company, $otherLandlord);
        Expense::factory()->create([
            'company_id' => $company->id,
            'property_id' => $otherProperty->id,
            'account_id' => $account->id,
            'description' => 'Someone Elses Expense',
        ]);

        $response = $this->actingAs($landlord, 'landlord')->get(route('landlord-portal.expenses.index'));

        $response->assertOk();
        $response->assertSee('Own Property Expense');
        $response->assertDontSee('Someone Elses Expense');
    }

    public function test_maintenance_requests_index_shows_only_requests_against_the_landlords_own_properties(): void
    {
        $company = Company::factory()->create();
        $landlord = $this->makePortalLandlord($company);
        $ownProperty = $this->makeProperty($company, $landlord);
        MaintenanceRequest::factory()->create(['company_id' => $company->id, 'property_id' => $ownProperty->id, 'title' => 'Own Property Leak']);

        $otherLandlord = Landlord::factory()->create(['company_id' => $company->id]);
        $otherProperty = $this->makeProperty($company, $otherLandlord);
        MaintenanceRequest::factory()->create(['company_id' => $company->id, 'property_id' => $otherProperty->id, 'title' => 'Someone Elses Leak']);

        $response = $this->actingAs($landlord, 'landlord')->get(route('landlord-portal.maintenance-requests.index'));

        $response->assertOk();
        $response->assertSee('Own Property Leak');
        $response->assertDontSee('Someone Elses Leak');
    }

    public function test_documents_index_shows_only_documents_attached_to_the_landlord_or_their_own_property(): void
    {
        $company = Company::factory()->create();
        $landlord = $this->makePortalLandlord($company);
        $ownProperty = $this->makeProperty($company, $landlord);

        Document::factory()->create(['company_id' => $company->id, 'documentable_type' => Landlord::class, 'documentable_id' => $landlord->id, 'title' => 'My Tax Document']);
        Document::factory()->create(['company_id' => $company->id, 'documentable_type' => Property::class, 'documentable_id' => $ownProperty->id, 'title' => 'My Property Deed']);

        $otherLandlord = Landlord::factory()->create(['company_id' => $company->id]);
        Document::factory()->create(['company_id' => $company->id, 'documentable_type' => Landlord::class, 'documentable_id' => $otherLandlord->id, 'title' => 'Someone Elses Document']);

        $response = $this->actingAs($landlord, 'landlord')->get(route('landlord-portal.documents.index'));

        $response->assertOk();
        $response->assertSee('My Tax Document');
        $response->assertSee('My Property Deed');
        $response->assertDontSee('Someone Elses Document');
    }
}
