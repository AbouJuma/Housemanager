<?php

namespace Tests\Feature\LandlordPortal;

use App\Modules\Accounting\Models\Account;
use App\Modules\Companies\Models\Company;
use App\Modules\Expenses\Models\Expense;
use App\Modules\Invoices\Models\Invoice;
use App\Modules\LandlordPortal\Support\LandlordAccess;
use App\Modules\Landlords\Models\Landlord;
use App\Modules\Leases\Models\Lease;
use App\Modules\Properties\Models\Building;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use App\Modules\Sales\Models\Buyer;
use App\Modules\Sales\Models\Sale;
use App\Modules\Tenants\Models\Tenant;
use App\Modules\Tenants\Notifications\TenantWelcome;
use App\Core\Tenancy\PermissionsTeam;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Landlords adding buildings, units and expenses and editing their
 * tenants and buyers - on the portal and through the app API - plus
 * date filters on their reports and expenses.
 */
class LandlordManagesMoreTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Landlord $landlord;

    protected Property $property;

    protected Building $building;

    protected Account $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->landlord = Landlord::factory()->create(['company_id' => $this->company->id, 'username' => 'juma.landlord']);
        $this->landlord->forceFill(['password' => 'Landlord#2026'])->save();
        $this->property = Property::factory()->create(['company_id' => $this->company->id, 'landlord_id' => $this->landlord->id, 'name' => 'Masaki Heights']);
        $this->building = Building::factory()->create(['company_id' => $this->company->id, 'property_id' => $this->property->id, 'name' => 'Block A']);
        $this->category = Account::factory()->create(['company_id' => $this->company->id, 'type' => 'expense', 'name' => 'Repairs', 'is_active' => true]);
    }

    protected function token(): string
    {
        return $this->postJson('/api/v1/portal/landlord/login', ['email' => 'juma.landlord', 'password' => 'Landlord#2026'])->json('token');
    }

    public function test_landlord_adds_buildings_and_units_and_edits_units(): void
    {
        $this->actingAs($this->landlord, 'landlord');

        $this->get(route('landlord-portal.properties.show', $this->property))->assertOk()
            ->assertSee(route('landlord-portal.buildings.create', $this->property), false)
            ->assertSee(route('landlord-portal.units.create', $this->building), false);

        $this->post(route('landlord-portal.buildings.store', $this->property), ['name' => 'Block B', 'floor_count' => 3])->assertRedirect();
        $blockB = Building::where('name', 'Block B')->firstOrFail();
        $this->assertSame($this->property->id, $blockB->property_id);

        $this->get(route('landlord-portal.units.create', $blockB))->assertOk()->assertDontSee('<option value="occupied"', false);
        $this->post(route('landlord-portal.units.store', $blockB), ['unit_number' => 'B1', 'type' => 'apartment', 'rent_amount' => '650,000', 'add_another' => 1])
            ->assertSessionHasNoErrors()->assertRedirect(route('landlord-portal.units.create', $blockB));
        $unit = Unit::where('unit_number', 'B1')->firstOrFail();
        $this->assertSame('vacant', $unit->status);
        $this->assertEquals(650000, $unit->rent_amount);

        // Same number twice in one building is refused; "occupied" can't be chosen.
        $this->post(route('landlord-portal.units.store', $blockB), ['unit_number' => 'B1', 'type' => 'apartment'])->assertSessionHasErrors('unit_number');
        $this->post(route('landlord-portal.units.store', $blockB), ['unit_number' => 'B2', 'type' => 'apartment', 'status' => 'occupied'])->assertSessionHasErrors('status');

        $this->put(route('landlord-portal.units.update', $unit), ['unit_number' => 'B1', 'type' => 'studio', 'status' => 'reserved', 'rent_amount' => 700000])->assertRedirect();
        $this->assertSame('reserved', $unit->fresh()->status);
        $this->assertEquals(700000, $unit->fresh()->rent_amount);

        // A rented unit keeps following its lease.
        $unit->forceFill(['status' => 'occupied'])->save();
        $this->put(route('landlord-portal.units.update', $unit), ['unit_number' => 'B1', 'type' => 'studio', 'status' => 'vacant', 'rent_amount' => 710000])->assertRedirect();
        $this->assertSame('occupied', $unit->fresh()->status);
        $this->assertEquals(710000, $unit->fresh()->rent_amount);

        // Not someone else's property.
        $other = Property::factory()->create(['company_id' => $this->company->id, 'landlord_id' => Landlord::factory()->create(['company_id' => $this->company->id])->id]);
        $this->post(route('landlord-portal.buildings.store', $other), ['name' => 'Hijack'])->assertForbidden();
        $this->assertDatabaseMissing('buildings', ['name' => 'Hijack']);

        // The app API does the same.
        $token = $this->token();
        $building = $this->withToken($token)->postJson("/api/v1/portal/landlord/properties/{$this->property->uuid}/buildings", ['name' => 'Block C'])->assertCreated();
        $created = $this->withToken($token)->postJson('/api/v1/portal/landlord/buildings/'.$building->json('data.uuid').'/units', ['unit_number' => 'C1', 'type' => 'shop', 'rent_amount' => 300000])->assertCreated();
        $this->withToken($token)->putJson('/api/v1/portal/landlord/units/'.$created->json('data.uuid'), ['unit_number' => 'C1', 'type' => 'shop', 'status' => 'under_maintenance'])
            ->assertOk()->assertJsonPath('data.status', 'under_maintenance');
        $this->withToken($token)->getJson('/api/v1/portal/landlord/property-options')->assertOk()->assertJsonPath('unit_types.0', 'apartment');
    }

    public function test_unit_permissions_can_be_switched_off(): void
    {
        $role = LandlordAccess::role($this->company->id);
        PermissionsTeam::runAs($this->company->id, fn () => $role->revokePermissionTo(['units.create', 'units.update']));

        $this->actingAs($this->landlord, 'landlord');
        $this->get(route('landlord-portal.properties.show', $this->property))->assertOk()->assertDontSee(route('landlord-portal.buildings.create', $this->property), false);
        $this->post(route('landlord-portal.buildings.store', $this->property), ['name' => 'Nope'])->assertForbidden();
        $this->withToken($this->token())->postJson("/api/v1/portal/landlord/buildings/{$this->building->uuid}/units", ['unit_number' => 'X', 'type' => 'room'])->assertForbidden();
    }

    public function test_landlord_records_and_edits_expenses_with_date_filters(): void
    {
        $this->actingAs($this->landlord, 'landlord');

        $this->get(route('landlord-portal.expenses.create'))->assertOk()->assertSee('Repairs');
        $this->post(route('landlord-portal.expenses.store'), [
            'property_id' => $this->property->id, 'account_id' => $this->category->id, 'description' => 'Gate repair',
            'amount' => '150,000', 'expense_date' => company_now()->toDateString(), 'payment_status' => 'paid',
            'payment_method' => 'mobile_money', 'paid_on' => company_now()->toDateString(),
        ])->assertSessionHasNoErrors()->assertRedirect(route('landlord-portal.expenses.index'));
        $expense = Expense::where('description', 'Gate repair')->firstOrFail();
        $this->assertEquals(150000, $expense->amount);
        $this->assertSame('paid', $expense->payment_status);

        // Older one, outside "today".
        Expense::factory()->create(['company_id' => $this->company->id, 'property_id' => $this->property->id, 'account_id' => $this->category->id,
            'description' => 'Old painting', 'amount' => 90000, 'expense_date' => company_now()->subMonths(2)->toDateString()]);

        $this->get(route('landlord-portal.expenses.index', ['period' => 'today']))->assertOk()->assertSee('Gate repair')->assertDontSee('Old painting')->assertSee('150,000.00');
        $this->get(route('landlord-portal.expenses.index', ['period' => 'this_year']))->assertOk()->assertSee('Old painting');
        $this->get(route('landlord-portal.expenses.index', ['from' => company_now()->subMonths(3)->toDateString(), 'to' => company_now()->subMonth()->toDateString()]))
            ->assertOk()->assertSee('Old painting')->assertDontSee('Gate repair');

        // Edit changes details only - never the amount.
        $this->put(route('landlord-portal.expenses.update', $expense), ['property_id' => $this->property->id, 'description' => 'Main gate repair', 'amount' => 1])->assertRedirect();
        $this->assertSame('Main gate repair', $expense->fresh()->description);
        $this->assertEquals(150000, $expense->fresh()->amount);

        // Can't book an expense on another landlord's property.
        $other = Property::factory()->create(['company_id' => $this->company->id, 'landlord_id' => Landlord::factory()->create(['company_id' => $this->company->id])->id]);
        $this->post(route('landlord-portal.expenses.store'), ['property_id' => $other->id, 'account_id' => $this->category->id, 'description' => 'X', 'amount' => 5, 'expense_date' => company_now()->toDateString(), 'payment_status' => 'unpaid'])
            ->assertSessionHasErrors('property_id');

        // App API: options, add, filter by period with totals.
        $token = $this->token();
        $this->withToken($token)->getJson('/api/v1/portal/landlord/expense-options')->assertOk()->assertJsonPath('categories.0.name', 'Repairs');
        $this->withToken($token)->postJson('/api/v1/portal/landlord/expenses', [
            'property_id' => $this->property->id, 'account_id' => $this->category->id, 'description' => 'Bulbs',
            'amount' => 20000, 'expense_date' => company_now()->toDateString(), 'payment_status' => 'unpaid',
        ])->assertCreated();
        $today = $this->withToken($token)->getJson('/api/v1/portal/landlord/expenses?period=today')->assertOk();
        $this->assertEqualsCanonicalizing(['Main gate repair', 'Bulbs'], collect($today->json('data'))->pluck('description')->all());
        $this->assertEquals(170000, $today->json('summary.total'));
        $this->assertEquals(20000, $today->json('summary.unpaid'));
    }

    public function test_profit_and_loss_breaks_down_by_day_week_or_month(): void
    {
        $this->actingAs($this->landlord, 'landlord');
        $this->get(route('landlord-portal.reports.profit-loss', ['period' => 'this_week', 'group' => 'day']))->assertOk()->assertSee(__('By day'));
        $this->get(route('landlord-portal.reports.profit-loss', ['period' => 'this_year', 'group' => 'week']))->assertOk()->assertSee(__('By week'));

        $token = $this->token();
        $this->withToken($token)->getJson('/api/v1/portal/landlord/reports/profit-loss?period=this_month')->assertOk()
            ->assertJsonPath('data.period', 'this_month')->assertJsonPath('data.group', 'day');
        $this->withToken($token)->getJson('/api/v1/portal/landlord/reports/profit-loss?period=this_year&group=month')->assertOk()
            ->assertJsonPath('data.group', 'month')->assertJsonCount(company_now()->month, 'data.breakdown');
    }

    public function test_landlord_edits_tenants_and_buyers_and_new_tenants_get_their_welcome(): void
    {
        Notification::fake();
        $unit = Unit::factory()->create(['company_id' => $this->company->id, 'building_id' => $this->building->id, 'status' => 'occupied']);
        $tenant = Tenant::factory()->create(['company_id' => $this->company->id, 'name' => 'Amina Old']);
        Lease::factory()->create(['company_id' => $this->company->id, 'tenant_id' => $tenant->id, 'unit_id' => $unit->id, 'status' => 'active']);
        $soldUnit = Unit::factory()->create(['company_id' => $this->company->id, 'building_id' => $this->building->id, 'status' => 'occupied']);
        $buyer = Buyer::factory()->create(['company_id' => $this->company->id, 'name' => 'Baraka Buyer', 'phone' => '0788111222']);
        Sale::factory()->create(['company_id' => $this->company->id, 'unit_id' => $soldUnit->id, 'new_buyer_id' => $buyer->id, 'status' => 'active', 'total_price' => 90000000]);
        $stranger = Tenant::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($this->landlord, 'landlord');

        $this->get(route('landlord-portal.tenants.edit', $tenant))->assertOk();
        $this->put(route('landlord-portal.tenants.update', $tenant), ['name' => 'Amina New', 'phone' => '0712999888'])->assertRedirect();
        $this->assertSame('Amina New', $tenant->fresh()->name);
        $this->get(route('landlord-portal.tenants.edit', $stranger))->assertNotFound();
        $this->put(route('landlord-portal.tenants.update', $stranger), ['name' => 'Hijack', 'phone' => '1'])->assertForbidden();

        $this->get(route('landlord-portal.buyers.index'))->assertOk()->assertSee('Baraka Buyer')->assertSee('Masaki Heights');
        $this->put(route('landlord-portal.buyers.update', $buyer), ['name' => 'Baraka B.', 'phone' => '0788111222', 'address' => 'Mbezi'])->assertRedirect();
        $this->assertSame('Baraka B.', $buyer->fresh()->name);

        $token = $this->token();
        $this->withToken($token)->putJson("/api/v1/portal/landlord/tenants/{$tenant->uuid}", ['name' => 'Amina App', 'phone' => '0712999888'])->assertOk()->assertJsonPath('data.name', 'Amina App');
        $this->withToken($token)->getJson('/api/v1/portal/landlord/buyers')->assertOk()->assertJsonPath('data.0.purchases.0.property.name', 'Masaki Heights');
        $this->withToken($token)->putJson("/api/v1/portal/landlord/buyers/{$buyer->uuid}", ['name' => 'Baraka App', 'phone' => '0788111222'])->assertOk()->assertJsonPath('data.name', 'Baraka App');

        // A tenant added by the landlord gets the same welcome as one added by staff.
        $this->post(route('landlord-portal.tenants.store'), ['name' => 'Neema Welcome', 'type' => 'individual', 'phone' => '0716000555'])->assertRedirect();
        Notification::assertSentTo(Tenant::where('name', 'Neema Welcome')->firstOrFail(), TenantWelcome::class);
        $this->withToken($token)->postJson('/api/v1/portal/landlord/tenants', ['name' => 'Zawadi App', 'type' => 'individual', 'phone' => '0716000666'])->assertCreated();
        Notification::assertSentTo(Tenant::where('name', 'Zawadi App')->firstOrFail(), TenantWelcome::class);
    }

    public function test_landlord_sees_pending_invoices_on_their_units_only(): void
    {
        $unit = Unit::factory()->create(['company_id' => $this->company->id, 'building_id' => $this->building->id, 'status' => 'occupied']);
        $tenant = Tenant::factory()->create(['company_id' => $this->company->id, 'name' => 'Amina Owes']);
        $lease = Lease::factory()->create(['company_id' => $this->company->id, 'tenant_id' => $tenant->id, 'unit_id' => $unit->id, 'status' => 'active']);
        Invoice::factory()->create(['company_id' => $this->company->id, 'lease_id' => $lease->id, 'invoice_number' => 'INV-OWED', 'status' => 'overdue', 'total_amount' => 500000, 'amount_paid' => 100000, 'due_date' => company_now()->subDays(5)]);
        Invoice::factory()->create(['company_id' => $this->company->id, 'lease_id' => $lease->id, 'invoice_number' => 'INV-PAID', 'status' => 'paid', 'total_amount' => 500000, 'amount_paid' => 500000, 'due_date' => company_now()->subMonth()]);

        // Another landlord's invoice never shows.
        $otherProperty = Property::factory()->create(['company_id' => $this->company->id, 'landlord_id' => Landlord::factory()->create(['company_id' => $this->company->id])->id]);
        $otherUnit = Unit::factory()->create(['company_id' => $this->company->id, 'building_id' => Building::factory()->create(['company_id' => $this->company->id, 'property_id' => $otherProperty->id])->id]);
        $otherLease = Lease::factory()->create(['company_id' => $this->company->id, 'unit_id' => $otherUnit->id, 'tenant_id' => Tenant::factory()->create(['company_id' => $this->company->id])->id]);
        Invoice::factory()->create(['company_id' => $this->company->id, 'lease_id' => $otherLease->id, 'invoice_number' => 'INV-NOTMINE', 'status' => 'sent']);

        $this->actingAs($this->landlord, 'landlord');
        $this->get(route('landlord-portal.invoices.index'))->assertOk()
            ->assertSee('INV-OWED')->assertSee('Amina Owes')->assertSee('400,000.00')
            ->assertDontSee('INV-PAID')->assertDontSee('INV-NOTMINE');
        $this->get(route('landlord-portal.invoices.index', ['status' => 'paid']))->assertOk()->assertSee('INV-PAID')->assertDontSee('INV-OWED');

        $token = $this->token();
        $pending = $this->withToken($token)->getJson('/api/v1/portal/landlord/invoices')->assertOk();
        $this->assertSame(['INV-OWED'], collect($pending->json('data'))->pluck('invoice_number')->all());
        $this->assertEquals(400000, $pending->json('summary.balance'));
        $this->assertTrue($pending->json('data.0.is_overdue'));
        $this->withToken($token)->getJson('/api/v1/portal/landlord/invoices?status=all&period=this_year')->assertOk();
    }
}
