<?php

namespace Tests\Feature\BuyerPortal;

use App\Models\User;
use App\Modules\Companies\Models\Company;
use App\Modules\Invoices\Models\Invoice;
use App\Modules\Properties\Models\Building;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use App\Modules\Sales\Models\Buyer;
use App\Modules\Sales\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Buyers sign in to their own web portal and follow their purchase. */
class BuyerPortalWebTest extends TestCase
{
    use RefreshDatabase;

    protected Buyer $buyer;

    protected Sale $sale;

    protected function setUp(): void
    {
        parent::setUp();

        $company = Company::factory()->create();
        $property = Property::factory()->create(['company_id' => $company->id, 'name' => 'Ocean View']);
        $building = Building::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);
        $unit = Unit::factory()->create(['company_id' => $company->id, 'building_id' => $building->id, 'unit_number' => 'OV-7']);

        $this->buyer = Buyer::factory()->create(['company_id' => $company->id, 'name' => 'Baraka Buyer', 'username' => 'baraka.buyer', 'phone' => '0788000444']);
        $this->buyer->forceFill(['password' => '0788000444'])->save();
        $this->sale = Sale::factory()->create(['company_id' => $company->id, 'unit_id' => $unit->id, 'new_buyer_id' => $this->buyer->id, 'status' => 'active', 'total_price' => 120000000, 'sale_date' => company_now()->subMonths(2)]);
        Invoice::factory()->create(['company_id' => $company->id, 'lease_id' => null, 'sale_id' => $this->sale->id, 'invoice_number' => 'INV-SALE-1', 'status' => 'sent', 'total_amount' => 10000000, 'amount_paid' => 0]);
    }

    public function test_buyer_signs_in_with_username_or_phone_and_sees_their_purchase(): void
    {
        $this->get(route('buyer-portal.login'))->assertOk()->assertSee(__('Buyer portal'));
        $this->get(route('buyer-portal.dashboard'))->assertRedirect(route('buyer-portal.login'));

        $this->post(route('buyer-portal.login'), ['email' => '0788 000 444', 'password' => '0788000444'])->assertRedirect(route('buyer-portal.dashboard'));
        $this->assertAuthenticatedAs($this->buyer, 'buyer');

        $this->get(route('buyer-portal.dashboard'))->assertOk()->assertSee('Baraka Buyer')->assertSee('Ocean View')->assertSee('OV-7');
        $this->get(route('buyer-portal.purchases.index'))->assertOk()->assertSee('Ocean View');
        $this->get(route('buyer-portal.purchases.show', $this->sale))->assertOk()->assertSee('INV-SALE-1');
        $this->get(route('buyer-portal.invoices.index'))->assertOk()->assertSee('INV-SALE-1');
        $invoice = Invoice::where('invoice_number', 'INV-SALE-1')->firstOrFail();
        $this->get(route('buyer-portal.invoices.show', $invoice))->assertOk()->assertSee('10,000,000.00');
        $this->get(route('buyer-portal.invoices.index', ['status' => 'paid']))->assertOk()->assertDontSee('INV-SALE-1');
    }

    public function test_buyers_only_see_their_own_records(): void
    {
        $other = Buyer::factory()->create(['company_id' => $this->buyer->company_id]);
        $this->actingAs($other, 'buyer');

        $this->get(route('buyer-portal.purchases.show', $this->sale))->assertForbidden();
        $this->get(route('buyer-portal.invoices.show', Invoice::where('invoice_number', 'INV-SALE-1')->firstOrFail()))->assertForbidden();
        $this->get(route('buyer-portal.invoices.index'))->assertOk()->assertDontSee('INV-SALE-1');
    }

    public function test_any_sign_in_page_takes_a_buyer_to_their_portal_and_wrong_details_fail(): void
    {
        $this->post(route('login'), ['email' => 'baraka.buyer', 'password' => '0788000444'])->assertRedirect(route('buyer-portal.dashboard'));
        auth('buyer')->logout();

        $this->from(route('buyer-portal.login'))->post(route('buyer-portal.login'), ['email' => 'baraka.buyer', 'password' => 'wrong'])
            ->assertRedirect(route('buyer-portal.login'))->assertSessionHasErrors('email');
        $this->assertGuest('buyer');

        // Staff details on the buyer page still reach the staff dashboard.
        User::factory()->create(['company_id' => $this->buyer->company_id, 'username' => 'desk.staff', 'password' => 'Staff#2026']);
        $this->post(route('buyer-portal.login'), ['email' => 'desk.staff', 'password' => 'Staff#2026'])->assertRedirect(route('dashboard'));
    }

    public function test_logout_returns_to_the_buyer_sign_in(): void
    {
        $this->actingAs($this->buyer, 'buyer')->post(route('buyer-portal.logout'))->assertRedirect(route('buyer-portal.login'));
        $this->assertGuest('buyer');
    }
}
