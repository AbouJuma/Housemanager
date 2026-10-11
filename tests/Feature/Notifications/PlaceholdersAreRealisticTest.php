<?php

namespace Tests\Feature\Notifications;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\MessageTemplate;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use App\Modules\Landlords\Models\Landlord;
use App\Modules\Landlords\Services\LandlordService;
use App\Modules\Leases\Services\LeaseService;
use App\Modules\PaymentReminders\Models\PaymentReminderRule;
use App\Modules\Properties\Models\Building;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use App\Modules\RentCollection\Notifications\InvoiceDueSoon;
use App\Modules\RentCollection\Notifications\InvoiceOverdue;
use App\Modules\Sales\Models\Buyer;
use App\Modules\Sales\Models\Installment;
use App\Modules\Sales\Notifications\PaymentDueSoon;
use App\Modules\Sales\Notifications\PaymentOverdue;
use App\Modules\Sales\Notifications\SaleCompleted;
use App\Modules\Sales\Services\SaleService;
use App\Modules\Tenants\Models\Tenant;
use App\Modules\Tenants\Services\TenantService;
use App\Notifications\TemplatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Every placeholder a template type lists (MessageTemplate::PLACEHOLDERS,
 * which drives the clickable field buttons on the Message Templates
 * screens) must be filled by the notification that sends it with a REAL
 * value - never missing, blank, "N/A" or a "-" filler - in the real
 * flows that send it. This is what stops a template showing text like
 * "Unit N/A ... Tsh 0".
 */
class PlaceholdersAreRealisticTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create(['bank_name' => 'CRDB Bank', 'account_number' => '0150123456789']);
    }

    /**
     * @return array<string, mixed>
     */
    protected function dataOf(TemplatedNotification $notification): array
    {
        return (fn () => $this->data)->call($notification);
    }

    protected function typeOf(TemplatedNotification $notification): string
    {
        return (fn () => $this->type)->call($notification);
    }

    protected function assertRealistic(TemplatedNotification $notification): void
    {
        $type = $this->typeOf($notification);
        $data = $this->dataOf($notification);
        $placeholders = MessageTemplate::placeholders($type);

        $this->assertNotEmpty($placeholders, "No placeholders listed for [{$type}].");

        foreach ($placeholders as $key) {
            $this->assertArrayHasKey($key, $data, "[{$type}] lists {{$key}} but the sender never fills it.");
            $value = $data[$key];
            $this->assertNotNull($value, "[{$type}] {{$key}} is null.");
            $this->assertNotContains((string) $value, ['', 'N/A', '-'], "[{$type}] {{$key}} is filler ('{$value}').");
        }

        foreach (array_keys($data) as $key) {
            $this->assertContains($key, $placeholders, "[{$type}] fills {{$key}} but it is not in MessageTemplate::PLACEHOLDERS - add it or drop it.");
        }
    }

    protected function assertSentRealistic(mixed $notifiable, string $class): void
    {
        $sent = Notification::sent($notifiable, $class);
        $this->assertCount(1, $sent, "Expected exactly one {$class}.");
        $this->assertRealistic($sent->first());
    }

    /**
     * @return array{Landlord, Unit}
     */
    protected function makeUnit(): array
    {
        $landlord = Landlord::factory()->create(['company_id' => $this->company->id]);
        $property = Property::factory()->create(['company_id' => $this->company->id, 'landlord_id' => $landlord->id]);
        $building = Building::factory()->create(['company_id' => $this->company->id, 'property_id' => $property->id]);
        $unit = Unit::factory()->create(['company_id' => $this->company->id, 'building_id' => $building->id, 'status' => 'vacant']);

        return [$landlord, $unit];
    }

    public function test_creating_a_tenant_fills_the_tenant_welcome_placeholders(): void
    {
        Notification::fake();

        $tenant = app(TenantService::class)->create([
            'company_id' => $this->company->id,
            'name' => 'Amina Tenant',
            'type' => 'individual',
            'status' => 'active',
            'phone' => '0712345678',
            'email' => 'amina@example.com',
        ]);

        $this->assertSentRealistic($tenant, \App\Modules\Tenants\Notifications\TenantWelcome::class);
    }

    public function test_creating_a_landlord_fills_the_landlord_welcome_placeholders(): void
    {
        Notification::fake();

        $landlord = app(LandlordService::class)->create([
            'company_id' => $this->company->id,
            'name' => 'Juma Landlord',
            'phone' => '0754321098',
            'email' => 'juma@example.com',
        ]);

        // A brand-new landlord genuinely has no properties yet, so the
        // sender words that as text rather than leaving the field blank.
        $this->assertSentRealistic($landlord, \App\Modules\Landlords\Notifications\LandlordWelcome::class);
    }

    public function test_creating_a_buyer_fills_the_buyer_welcome_placeholders(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['company_id' => $this->company->id]);
        PermissionsTeam::runAs($this->company->id, function () use ($admin): void {
            $role = Role::findOrCreate('Admin', 'web');
            $role->syncPermissions(['buyers.viewAny', 'buyers.view', 'buyers.create']);
            $admin->assignRole($role);
        });

        $this->actingAs($admin)->post(route('buyers.store'), ['name' => 'Asha Buyer', 'phone' => '0788111222', 'email' => 'asha@example.com'])
            ->assertRedirect();

        $buyer = Buyer::where('name', 'Asha Buyer')->firstOrFail();
        $this->assertSentRealistic($buyer, \App\Modules\Sales\Notifications\BuyerWelcome::class);
    }

    public function test_creating_a_lease_with_a_deposit_fills_every_lease_and_payment_placeholder(): void
    {
        Notification::fake();
        [$landlord, $unit] = $this->makeUnit();
        $tenant = Tenant::factory()->create(['company_id' => $this->company->id, 'phone' => '0712345678', 'control_number' => '9123456780']);

        app(LeaseService::class)->create([
            'company_id' => $this->company->id,
            'tenant_id' => $tenant->id,
            'unit_id' => $unit->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'rent_amount' => 200000,
            'deposit_amount' => 150000,
        ]);

        $this->assertSentRealistic($tenant, \App\Modules\Leases\Notifications\LeaseCreatedTenantNotice::class);
        $this->assertSentRealistic($landlord, \App\Modules\Leases\Notifications\LeaseCreatedLandlordNotice::class);
        $this->assertSentRealistic($tenant, \App\Modules\Invoices\Notifications\LeaseInvoiceCreated::class);
        $this->assertSentRealistic($tenant, \App\Modules\Invoices\Notifications\TenantPaymentConfirmation::class);
        $this->assertSentRealistic($landlord, \App\Modules\Invoices\Notifications\LandlordPaymentConfirmation::class);
    }

    public function test_creating_a_sale_and_paying_an_installment_fills_every_sale_placeholder(): void
    {
        [$landlord, $unit] = $this->makeUnit();
        $buyer = Buyer::factory()->create(['company_id' => $this->company->id, 'phone' => '0788111222']);
        Notification::fake();

        $service = app(SaleService::class);
        $sale = $service->create([
            'company_id' => $this->company->id,
            'unit_id' => $unit->id,
            'new_buyer_id' => $buyer->id,
            'sale_date' => now()->toDateString(),
            'total_price' => 10000,
            'down_payment' => 2000,
            'installment_period' => 4,
            'installment_amount' => 2000,
        ]);
        $this->assertSentRealistic($buyer, \App\Modules\Sales\Notifications\SaleCreatedNotice::class);

        $service->recordPayment($sale->installments()->orderBy('installment_number')->first(), 2000);
        $this->assertSentRealistic($buyer, \App\Modules\Invoices\Notifications\BuyerInvoicePaymentConfirmation::class);
        $this->assertSentRealistic($landlord, \App\Modules\Invoices\Notifications\LandlordSalePaymentConfirmation::class);

        $this->assertRealistic(new SaleCompleted($sale->fresh()));
    }

    public function test_reminder_notifications_fill_their_placeholders(): void
    {
        [$landlord, $unit] = $this->makeUnit();
        $tenant = Tenant::factory()->create(['company_id' => $this->company->id]);
        $buyer = Buyer::factory()->create(['company_id' => $this->company->id]);
        Notification::fake();

        $lease = app(LeaseService::class)->create([
            'company_id' => $this->company->id,
            'tenant_id' => $tenant->id,
            'unit_id' => $unit->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'rent_amount' => 200000,
        ]);
        $invoice = $lease->invoices()->first();

        $this->assertRealistic(new InvoiceDueSoon($invoice));
        $this->assertRealistic(new InvoiceOverdue($invoice));

        [, $unit2] = $this->makeUnit();
        $sale = app(SaleService::class)->create([
            'company_id' => $this->company->id,
            'unit_id' => $unit2->id,
            'new_buyer_id' => $buyer->id,
            'sale_date' => now()->toDateString(),
            'total_price' => 10000,
            'down_payment' => 2000,
            'installment_period' => 4,
            'installment_amount' => 2000,
        ]);
        $installment = Installment::where('sale_id', $sale->id)->first();

        $this->assertRealistic(new PaymentDueSoon($installment));
        $this->assertRealistic(new PaymentOverdue($installment));
    }
}
