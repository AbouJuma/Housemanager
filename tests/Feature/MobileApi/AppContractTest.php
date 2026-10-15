<?php

namespace Tests\Feature\MobileApi;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Accounting\Models\Account;
use App\Modules\Companies\Models\Company;
use App\Modules\Documents\Models\Document;
use App\Modules\Expenses\Models\Expense;
use App\Modules\Invoices\Models\Invoice;
use App\Modules\Landlords\Services\LandlordService;
use App\Modules\Leases\Models\Lease;
use App\Modules\Leases\Services\LeaseService;
use App\Modules\Maintenance\Models\MaintenanceRequest;
use App\Modules\Properties\Models\Building;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use App\Modules\Sales\Models\Buyer;
use App\Modules\Sales\Services\SaleService;
use App\Modules\Tenants\Services\TenantService;
use App\Modules\Users\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The mobile app's whole API surface, exercised the way the app uses it:
 * every role signs in (by username or phone), every endpoint a screen
 * calls answers 200, every create the app sends succeeds, and each
 * response is saved to mobile/test/fixtures/api so the Flutter side
 * (mobile/test/api_contract_test.dart) parses the REAL responses with
 * its own models - catching type mismatches before a phone does.
 */
class AppContractTest extends TestCase
{
    use RefreshDatabase;

    protected string $fixtureDir;

    /** @var array<string, string> role => bearer token */
    protected array $tokens = [];

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->fixtureDir = base_path('mobile/test/fixtures/api');
        File::ensureDirectoryExists($this->fixtureDir);
    }

    public function test_every_screen_endpoint_answers_and_every_create_succeeds(): void
    {
        $company = Company::factory()->create(['name' => 'Contract Homes', 'bank_name' => 'CRDB Bank', 'account_number' => '0150123456789']);

        // --- Staff (created the way an admin creates one - default password = phone)
        $staff = PermissionsTeam::runAs($company->id, function () use ($company) {
            Role::findOrCreate('Admin', 'web')->syncPermissions(Permission::where('guard_name', 'web')->pluck('name'));

            return app(UserService::class)->create([
                'name' => 'Sara Staff', 'phone' => '0711000111', 'company_id' => $company->id, 'roles' => ['Admin'],
            ]);
        });

        // --- Landlord, property, building, units
        $landlord = app(LandlordService::class)->create(['company_id' => $company->id, 'name' => 'Juma Landlord', 'phone' => '0754000222']);
        $property = Property::factory()->create(['company_id' => $company->id, 'landlord_id' => $landlord->id, 'state' => 'Dar es Salaam', 'city' => 'Kinondoni', 'area' => 'Masaki']);
        $building = Building::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);
        $leasedUnit = Unit::factory()->create(['company_id' => $company->id, 'building_id' => $building->id, 'status' => 'vacant', 'rent_amount' => 450000]);
        $soldUnit = Unit::factory()->create(['company_id' => $company->id, 'building_id' => $building->id, 'status' => 'vacant', 'rent_amount' => 300000]);
        $spareUnit = Unit::factory()->create(['company_id' => $company->id, 'building_id' => $building->id, 'status' => 'vacant', 'rent_amount' => 500000]);

        // --- Tenant with a lease (deposit -> invoice + payment)
        $tenant = app(TenantService::class)->create(['company_id' => $company->id, 'name' => 'Amina Tenant', 'type' => 'individual', 'status' => 'active', 'phone' => '0712000333']);
        $lease = app(LeaseService::class)->create([
            'company_id' => $company->id, 'tenant_id' => $tenant->id, 'unit_id' => $leasedUnit->id,
            'start_date' => now()->toDateString(), 'end_date' => now()->addYear()->toDateString(),
            'rent_amount' => 450000, 'deposit_amount' => 450000,
        ]);
        MaintenanceRequest::factory()->create(['company_id' => $company->id, 'property_id' => $property->id, 'unit_id' => $leasedUnit->id, 'tenant_id' => $tenant->id]);
        $account = Account::factory()->create(['company_id' => $company->id]);
        Expense::factory()->create(['company_id' => $company->id, 'property_id' => $property->id, 'account_id' => $account->id]);
        $tenant->documents()->save(Document::factory()->make(['company_id' => $company->id]));
        $landlord->documents()->save(Document::factory()->make(['company_id' => $company->id]));

        // --- Buyer with a sale and one installment paid
        $buyer = Buyer::factory()->create(['company_id' => $company->id, 'name' => 'Asha Buyer', 'phone' => '0788000444']);
        $buyer->forceFill(['password' => '0788000444'])->save();
        $sales = app(SaleService::class);
        $sale = $sales->create([
            'company_id' => $company->id, 'unit_id' => $soldUnit->id, 'new_buyer_id' => $buyer->id,
            'sale_date' => now()->toDateString(), 'total_price' => 30000000, 'down_payment' => 6000000,
            'installment_period' => 4, 'installment_amount' => 6000000,
        ]);
        $sales->recordPayment($sale->installments()->orderBy('installment_number')->first(), 6000000);

        // --- Sign in like the app: username for some, phone (any format) for others
        $this->tokens['staff'] = $this->login('/auth/login', $staff->username, '0711000111');
        $this->tokens['tenant'] = $this->login('/portal/tenant/login', '+255 712 000 333', '0712000333');
        $this->tokens['landlord'] = $this->login('/portal/landlord/login', $landlord->username, '255754000222');
        $this->tokens['buyer'] = $this->login('/portal/buyer/login', '0788000444', '0788000444');

        $invoice = Invoice::query()->where('lease_id', $lease->id)->firstOrFail();
        $buyerInvoice = Invoice::query()->whereNull('lease_id')->firstOrFail();
        $maintenance = MaintenanceRequest::query()->firstOrFail();

        // --- Every GET a screen makes
        $gets = [
            'tenant' => [
                'tenant_dashboard' => '/portal/tenant/dashboard',
                'tenant_leases' => '/portal/tenant/leases',
                'tenant_lease' => "/portal/tenant/leases/{$lease->uuid}",
                'tenant_invoices' => '/portal/tenant/invoices',
                'tenant_invoice' => "/portal/tenant/invoices/{$invoice->uuid}",
                'tenant_maintenance' => '/portal/tenant/maintenance-requests',
                'tenant_maintenance_detail' => "/portal/tenant/maintenance-requests/{$maintenance->uuid}",
                'tenant_documents' => '/portal/tenant/documents',
            ],
            'landlord' => [
                'landlord_dashboard' => '/portal/landlord/dashboard',
                'landlord_properties' => '/portal/landlord/properties',
                'landlord_property' => "/portal/landlord/properties/{$property->uuid}",
                'landlord_expenses' => '/portal/landlord/expenses',
                'landlord_maintenance' => '/portal/landlord/maintenance-requests',
                'landlord_documents' => '/portal/landlord/documents',
                'landlord_tenants' => '/portal/landlord/tenants',
                'landlord_tenant' => "/portal/landlord/tenants/{$tenant->uuid}",
                'landlord_profit_loss' => '/portal/landlord/reports/profit-loss',
                'landlord_units' => '/portal/landlord/reports/units',
                'landlord_property_options' => '/portal/landlord/property-options',
                'landlord_invoices' => '/portal/landlord/invoices?status=all',
                'landlord_buyers' => '/portal/landlord/buyers',
                'landlord_buyer' => "/portal/landlord/buyers/{$buyer->uuid}",
            ],
            'buyer' => [
                'buyer_dashboard' => '/portal/buyer/dashboard',
                'buyer_sales' => '/portal/buyer/sales',
                'buyer_sale' => "/portal/buyer/sales/{$sale->uuid}",
                'buyer_invoices' => '/portal/buyer/invoices',
                'buyer_invoice' => "/portal/buyer/invoices/{$buyerInvoice->uuid}",
            ],
            'staff' => [
                'staff_tenants' => '/tenants',
                'staff_tenant' => "/tenants/{$tenant->uuid}",
                'staff_leases' => '/leases',
                'staff_lease' => "/leases/{$lease->uuid}",
                'staff_invoices' => '/invoices',
                'staff_invoice' => "/invoices/{$invoice->uuid}",
                'staff_properties' => '/properties',
                'staff_buildings' => "/properties/{$property->uuid}/buildings",
                'staff_units' => "/buildings/{$building->uuid}/units",
                'staff_rent_roll' => '/reports/rent-roll',
                'staff_occupancy' => '/reports/occupancy',
            ],
        ];

        foreach ($gets as $role => $endpoints) {
            foreach ($endpoints as $name => $path) {
                $response = $this->api($role)->getJson('/api/v1'.$path);
                $this->assertSame(200, $response->status(), "[{$role}] GET {$path} answered {$response->status()}: ".$response->getContent());
                $this->saveFixture($name, $response->json());
            }
        }

        // --- A landlord adding/editing a property and adding a tenant (app forms).
        $newProperty = $this->api('landlord')->postJson('/api/v1/portal/landlord/properties', ['name' => 'Juma Towers', 'type' => 'residential', 'state' => 'Dar es Salaam', 'city' => 'Kinondoni', 'area' => 'Mbezi Beach']);
        $this->assertSame(201, $newProperty->status(), 'POST landlord properties: '.$newProperty->getContent());
        $this->saveFixture('landlord_property_created', $newProperty->json());
        $edited = $this->api('landlord')->putJson('/api/v1/portal/landlord/properties/'.$newProperty->json('data.uuid'), ['name' => 'Juma Towers', 'type' => 'residential', 'status' => 'active', 'description' => 'Near the beach']);
        $this->assertSame(200, $edited->status(), 'PUT landlord properties: '.$edited->getContent());
        $newTenant = $this->api('landlord')->postJson('/api/v1/portal/landlord/tenants', ['name' => 'Neema Mpangaji', 'type' => 'individual', 'phone' => '0716000555']);
        $this->assertSame(201, $newTenant->status(), 'POST landlord tenants: '.$newTenant->getContent());
        $this->saveFixture('landlord_tenant_created', $newTenant->json());

        $this->assertSame(200, $this->api('landlord')->putJson('/api/v1/portal/landlord/tenants/'.$newTenant->json('data.uuid'), ['name' => 'Neema M.', 'phone' => '0716000555'])->status());
        $block = $this->api('landlord')->postJson('/api/v1/portal/landlord/properties/'.$newProperty->json('data.uuid').'/buildings', ['name' => 'Block A']);
        $this->assertSame(201, $block->status(), 'POST buildings: '.$block->getContent());
        $this->saveFixture('landlord_building_created', $block->json());
        $newUnit = $this->api('landlord')->postJson('/api/v1/portal/landlord/buildings/'.$block->json('data.uuid').'/units', ['unit_number' => 'A1', 'type' => 'apartment', 'rent_amount' => 450000]);
        $this->assertSame(201, $newUnit->status(), 'POST units: '.$newUnit->getContent());
        $this->saveFixture('landlord_unit_saved', $newUnit->json());
        $this->assertSame(200, $this->api('landlord')->putJson('/api/v1/portal/landlord/units/'.$newUnit->json('data.uuid'), ['unit_number' => 'A1', 'type' => 'apartment', 'status' => 'reserved'])->status());
        Account::factory()->create(['company_id' => $company->id, 'type' => 'expense', 'name' => 'Repairs', 'is_active' => true]);
        $options = $this->api('landlord')->getJson('/api/v1/portal/landlord/expense-options')->json();
        $this->saveFixture('landlord_expense_options', $options);
        $newExpense = $this->api('landlord')->postJson('/api/v1/portal/landlord/expenses', ['property_id' => $options['properties'][0]['id'], 'account_id' => $options['categories'][0]['id'], 'description' => 'Gate repair', 'amount' => 150000, 'expense_date' => now()->toDateString(), 'payment_status' => 'unpaid']);
        $this->assertSame(201, $newExpense->status(), 'POST landlord expenses: '.$newExpense->getContent());
        $this->saveFixture('landlord_expense_saved', $newExpense->json());
        $this->assertSame(200, $this->api('landlord')->putJson('/api/v1/portal/landlord/expenses/'.$newExpense->json('data.uuid'), ['property_id' => $options['properties'][0]['id'], 'description' => 'Main gate repair'])->status());
        $this->saveFixture('landlord_expenses_today', $this->api('landlord')->getJson('/api/v1/portal/landlord/expenses?period=today')->json());
        $this->saveFixture('landlord_profit_loss_month', $this->api('landlord')->getJson('/api/v1/portal/landlord/reports/profit-loss?period=this_month&group=week')->json());

        // --- A landlord renting out a free unit (app lease form) - a unit of
        // its own, so the vacant units checked further down stay vacant.
        $leaseUnit = $this->api('landlord')->postJson('/api/v1/portal/landlord/buildings/'.$block->json('data.uuid').'/units', ['unit_number' => 'A2', 'type' => 'apartment', 'rent_amount' => 500000, 'rent_period' => 'monthly']);
        $this->assertSame(201, $leaseUnit->status(), 'POST lease unit: '.$leaseUnit->getContent());
        $leaseOptions = $this->api('landlord')->getJson('/api/v1/portal/landlord/lease-options');
        $this->assertSame(200, $leaseOptions->status(), 'GET lease-options: '.$leaseOptions->getContent());
        $this->saveFixture('landlord_lease_options', $leaseOptions->json());
        $this->assertContains($leaseUnit->json('data.uuid'), collect($leaseOptions->json('units'))->pluck('uuid')->all());
        if (true) {
            $landlordLease = $this->api('landlord')->postJson('/api/v1/portal/landlord/leases', [
                'unit_id' => $leaseUnit->json('data.uuid'), 'tenant_id' => $newTenant->json('data.uuid'),
                'billing_cycle' => 'quarterly', 'start_date' => now()->toDateString(), 'periods' => 4,
            ]);
            $this->assertSame(201, $landlordLease->status(), 'POST landlord leases: '.$landlordLease->getContent());
            $this->saveFixture('landlord_lease_created', $landlordLease->json());
        }

        // --- The super admin in the app: companies, created with their admin.
        $owner = User::factory()->create(['username' => 'platform.owner', 'password' => 'Owner#2026']);
        $owner->forceFill(['company_id' => null])->save();
        $this->tokens['superadmin'] = $this->login('/auth/login', 'platform.owner', 'Owner#2026', 'superadmin');
        $newCompany = $this->api('superadmin')->postJson('/api/v1/companies', ['name' => 'Kilimanjaro Homes', 'admin_name' => 'Asha Admin', 'admin_email' => 'asha@kili.test']);
        $this->assertSame(201, $newCompany->status(), 'POST companies: '.$newCompany->getContent());
        $this->saveFixture('platform_company_created', $newCompany->json());
        $this->saveFixture('platform_companies', $this->api('superadmin')->getJson('/api/v1/companies')->json());
        $this->saveFixture('platform_company', $this->api('superadmin')->getJson('/api/v1/companies/'.$newCompany->json('data.uuid'))->json());
        $this->saveFixture('superadmin_account', $this->api('superadmin')->getJson('/api/v1/auth/me')->json());

        // --- Every create/update the app sends, with the app's exact payloads
        $newTenant = $this->api('staff')->postJson('/api/v1/tenants', ['name' => 'New Tenant', 'type' => 'individual', 'status' => 'active', 'phone' => '0713000555']);
        $this->assertSame(201, $newTenant->status(), 'POST /tenants: '.$newTenant->getContent());
        $this->saveFixture('staff_tenant_created', $newTenant->json());
        $newTenantUuid = $newTenant->json('data.uuid');

        $updated = $this->api('staff')->putJson("/api/v1/tenants/{$newTenantUuid}", ['name' => 'New Tenant Renamed', 'type' => 'individual', 'status' => 'active', 'phone' => '0713000555']);
        $this->assertSame(200, $updated->status(), 'PUT /tenants: '.$updated->getContent());

        $newLease = $this->api('staff')->postJson('/api/v1/leases', [
            'tenant_id' => $newTenantUuid, 'unit_id' => $spareUnit->uuid,
            'start_date' => now()->toDateString(), 'end_date' => now()->addYear()->toDateString(),
            'billing_cycle' => 'monthly', 'rent_amount' => 500000, 'deposit_amount' => 500000,
        ]);
        $this->assertSame(201, $newLease->status(), 'POST /leases: '.$newLease->getContent());
        $this->saveFixture('staff_lease_created', $newLease->json());

        $leaseUpdate = $this->api('staff')->putJson('/api/v1/leases/'.$newLease->json('data.uuid'), [
            'end_date' => now()->addYears(2)->toDateString(), 'billing_cycle' => 'monthly', 'rent_amount' => 550000, 'status' => 'active',
        ]);
        $this->assertSame(200, $leaseUpdate->status(), 'PUT /leases: '.$leaseUpdate->getContent());

        $newInvoice = $this->api('staff')->postJson('/api/v1/invoices', [
            'lease_id' => $lease->uuid, 'issue_date' => now()->toDateString(), 'due_date' => now()->addDays(14)->toDateString(),
            'items' => [['description' => 'Water', 'quantity' => 1, 'unit_price' => 25000]],
        ]);
        $this->assertSame(201, $newInvoice->status(), 'POST /invoices: '.$newInvoice->getContent());
        $this->saveFixture('staff_invoice_created', $newInvoice->json());

        // Sent first - payments are only accepted on sent invoices.
        $send = $this->api('staff')->postJson('/api/v1/invoices/'.$newInvoice->json('data.uuid').'/send');
        $this->assertContains($send->status(), [200, 201], 'POST /invoices/{uuid}/send: '.$send->getContent());

        $payment = $this->api('staff')->postJson('/api/v1/invoices/'.$newInvoice->json('data.uuid').'/payments', [
            'amount' => 10000, 'paid_on' => now()->toDateString(), 'method' => 'cash',
        ]);
        $this->assertContains($payment->status(), [200, 201], 'POST /invoices/{uuid}/payments: '.$payment->getContent());
        $this->saveFixture('staff_payment_recorded', $payment->json());

        $request = $this->api('tenant')->postJson('/api/v1/portal/tenant/maintenance-requests', [
            'lease_id' => $lease->uuid, 'title' => 'Leaking tap', 'description' => 'Kitchen tap drips', 'category' => 'plumbing', 'priority' => 'medium',
        ]);
        $this->assertSame(201, $request->status(), 'POST maintenance-requests: '.$request->getContent());

        // --- Modules the app offers to other staff roles (accountant,
        // caretaker/cleaner, security, storekeeper...) - list + one record.
        \App\Modules\VisitorManagement\Models\Visitor::factory()->create(['company_id' => $company->id, 'property_id' => $property->id, 'check_out_at' => null]);
        \App\Modules\Vendors\Models\Vendor::factory()->create(['company_id' => $company->id]);
        \App\Modules\Inventory\Models\InventoryItem::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);
        foreach ([
            'maintenance' => '/maintenance-requests',
            'expenses' => '/expenses',
            'properties' => '/properties',
            'visitors' => '/visitors',
            'vendors' => '/vendors',
            'inventory' => '/inventory-items',
            'documents' => '/documents',
        ] as $name => $endpoint) {
            $list = $this->api('staff')->getJson('/api/v1'.$endpoint);
            $this->assertSame(200, $list->status(), "GET {$endpoint}: ".$list->getContent());
            $this->assertNotEmpty($list->json('data'), "GET {$endpoint} returned nothing to show");
            $this->saveFixture("module_{$name}", $list->json());

            $uuid = $list->json('data.0.uuid');
            $one = $this->api('staff')->getJson("/api/v1{$endpoint}/{$uuid}");
            $this->assertSame(200, $one->status(), "GET {$endpoint}/{uuid}: ".$one->getContent());
            $this->saveFixture("module_{$name}_detail", $one->json());
            $firstUuid[$name] = $uuid;
        }

        // --- What those roles do from a record's page.
        $job = $this->api('staff')->patchJson('/api/v1/maintenance-requests/'.$firstUuid['maintenance'].'/status', [
            'status' => 'completed', 'notes' => 'Washer replaced',
        ]);
        $this->assertSame(200, $job->status(), 'PATCH maintenance-requests/{uuid}/status: '.$job->getContent());
        $this->assertSame('completed', $job->json('data.status'));
        $this->assertNotNull($job->json('data.completed_date'));
        $this->assertStringContainsString('Washer replaced', (string) $job->json('data.notes'));
        $this->saveFixture('module_maintenance_status_updated', $job->json());
        $this->assertSame(422, $this->api('staff')->patchJson('/api/v1/maintenance-requests/'.$firstUuid['maintenance'].'/status', ['status' => 'nonsense'])->status());

        $paid = $this->api('staff')->postJson('/api/v1/expenses/'.$firstUuid['expenses'].'/mark-paid', [
            'payment_method' => 'mobile_money', 'paid_on' => now()->toDateString(),
        ]);
        $this->assertSame(200, $paid->status(), 'POST expenses/{uuid}/mark-paid: '.$paid->getContent());
        $this->assertSame('paid', $paid->json('data.payment_status'));
        $this->saveFixture('module_expense_marked_paid', $paid->json());

        $out = $this->api('staff')->postJson('/api/v1/visitors/'.$firstUuid['visitors'].'/check-out');
        $this->assertSame(200, $out->status(), 'POST visitors/{uuid}/check-out: '.$out->getContent());
        $this->assertFalse($out->json('data.is_checked_in'));
        $this->saveFixture('module_visitor_checked_out', $out->json());

        // --- Reports filtered by status (the app's status pills)
        $vacantOnly = $this->api('staff')->getJson('/api/v1/reports/rent-roll?status=vacant');
        $this->assertSame(200, $vacantOnly->status());
        $this->assertNotEmpty($vacantOnly->json('data'));
        $this->assertSame(['vacant'], collect($vacantOnly->json('data'))->pluck('status')->unique()->values()->all());
        $this->saveFixture('staff_rent_roll_vacant', $vacantOnly->json());
        $this->assertSame(200, $this->api('staff')->getJson('/api/v1/reports/occupancy?status=active')->status());

        // --- My account + notifications, for every role. Notifications are
        // faked in this test, so seed one real inbox row per person (staff
        // gets a genuine "access updated" payload) for the app to render.
        $people = ['staff' => $staff, 'tenant' => $tenant, 'landlord' => $landlord, 'buyer' => $buyer];
        foreach ($people as $role => $person) {
            $data = $role === 'staff'
                ? (new \App\Modules\Users\Notifications\AccessChanged(['invoices.create'], ['tenants.delete'], ['Admin']))->toArray($person)
                : ['title' => 'Welcome message sent', 'message' => "Welcome message sent to {$person->name}."];
            $person->notifications()->create(['id' => (string) \Illuminate\Support\Str::uuid(), 'type' => 'seeded', 'data' => $data]);
        }

        $accounts = ['staff' => '/auth', 'tenant' => '/portal/tenant', 'landlord' => '/portal/landlord', 'buyer' => '/portal/buyer'];
        foreach ($accounts as $role => $base) {
            $me = $this->api($role)->getJson('/api/v1'.($role === 'staff' ? '/auth/me' : "{$base}/profile"));
            $this->assertSame(200, $me->status(), "[{$role}] profile: ".$me->getContent());
            $this->assertNotEmpty($me->json('profile.username'));
            $this->saveFixture("{$role}_account", $me->json());

            $updated = $this->api($role)->putJson("/api/v1{$base}/profile", [
                'name' => $me->json('profile.name'),
                'username' => $me->json('profile.username').'.x',
                'email' => "{$role}@example.test",
                'phone' => $me->json('profile.phone'),
            ]);
            $this->assertSame(200, $updated->status(), "[{$role}] profile update: ".$updated->getContent());
            $this->assertSame($me->json('profile.username').'.x', $updated->json('profile.username'));

            $inbox = $this->api($role)->getJson('/api/v1'.($role === 'staff' ? '' : $base).'/notifications');
            $this->assertSame(200, $inbox->status(), "[{$role}] notifications: ".$inbox->getContent());
            $this->assertIsInt($inbox->json('unread_count'));
            $this->saveFixture("{$role}_notifications", $inbox->json());
            if ($first = $inbox->json('data.0.id')) {
                $read = $this->api($role)->postJson('/api/v1'.($role === 'staff' ? '' : $base)."/notifications/{$first}/read");
                $this->assertSame(200, $read->status());
                $this->assertTrue($read->json('data.read'));
            }
            $this->assertSame(200, $this->api($role)->postJson('/api/v1'.($role === 'staff' ? '' : $base).'/notifications/read-all')->status());
        }
        $this->assertArrayHasKey('permissions', $this->api('staff')->getJson('/api/v1/auth/me')->json());

        // Password change (tenant): wrong current password refused, right one works.
        $this->api('tenant')->putJson('/api/v1/portal/tenant/password', [
            'current_password' => 'nope', 'password' => 'NewPass#2026', 'password_confirmation' => 'NewPass#2026',
        ])->assertStatus(422)->assertJsonValidationErrors('current_password');
        $this->api('tenant')->putJson('/api/v1/portal/tenant/password', [
            'current_password' => '+255 712 000 333', 'password' => 'NewPass#2026', 'password_confirmation' => 'NewPass#2026',
        ])->assertOk();
        $this->app['auth']->forgetGuards();
        $this->postJson('/api/v1/portal/tenant/login', ['email' => '0712000333', 'password' => 'NewPass#2026'])->assertOk();

        // Online payment depends on a configured gateway - it must answer
        // with a readable message either way, never a server error.
        foreach (['tenant' => "/portal/tenant/invoices/{$invoice->uuid}/pay", 'buyer' => "/portal/buyer/invoices/{$buyerInvoice->uuid}/pay"] as $role => $path) {
            $pay = $this->api($role)->postJson('/api/v1'.$path, ['amount' => 1000]);
            $this->assertLessThan(500, $pay->status(), "[{$role}] POST {$path} answered {$pay->status()}: ".$pay->getContent());
            $this->assertIsString($pay->json('message'), "[{$role}] POST {$path} gave no message.");
        }
    }

    protected function login(string $path, string $identifier, string $password, ?string $fixture = null): string
    {
        $response = $this->postJson('/api/v1'.$path, ['email' => $identifier, 'password' => $password]);
        $this->assertSame(200, $response->status(), "Login {$path} as {$identifier}: ".$response->getContent());
        // /auth/login -> login_staff, /portal/tenant/login -> login_tenant ...
        $role = $fixture ?? ($path === '/auth/login' ? 'staff' : explode('/', trim($path, '/'))[1]);
        $this->saveFixture("login_{$role}", $response->json());

        return $response->json('token');
    }

    protected function api(string $role): self
    {
        // A fresh guard per request - Sanctum caches the resolved user.
        $this->app['auth']->forgetGuards();
        // ...and no company carried over from the previous request (a real
        // request starts without one; the super admin has none).
        app(\App\Core\Tenancy\CompanyContext::class)->clear();

        return $this->withHeader('Authorization', 'Bearer '.$this->tokens[$role]);
    }

    protected function saveFixture(string $name, mixed $json): void
    {
        File::put("{$this->fixtureDir}/{$name}.json", json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}
