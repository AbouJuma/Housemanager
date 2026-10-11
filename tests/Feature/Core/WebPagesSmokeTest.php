<?php

namespace Tests\Feature\Core;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\MessageTemplate;
use App\Models\User;
use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Companies\Models\Company;
use App\Modules\Documents\Models\Document;
use App\Modules\Expenses\Models\Expense;
use App\Modules\Inventory\Models\InventoryItem;
use App\Modules\Invoices\Models\Invoice;
use App\Modules\Landlords\Services\LandlordService;
use App\Modules\Leases\Services\LeaseService;
use App\Modules\Maintenance\Models\MaintenanceRequest;
use App\Modules\Properties\Models\Building;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use App\Modules\Sales\Models\Buyer;
use App\Modules\Sales\Services\SaleService;
use App\Modules\Subscriptions\Models\SubscriptionPlan;
use App\Modules\Tenants\Services\TenantService;
use App\Modules\Vendors\Models\Vendor;
use App\Modules\VisitorManagement\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as RouteDefinition;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Opens every GET page of the web app - back office as a company admin
 * holding every permission, the SaaS-owner pages as the owner, and the
 * tenant and landlord portals as their own users - against realistic
 * data, and fails on any server error. A page refusing access (403) is
 * allowed; crashing (500) never is.
 */
class WebPagesSmokeTest extends TestCase
{
    use RefreshDatabase;

    /** URIs that aren't pages: downloads of raw storage, auth tokens, etc. */
    protected const SKIP = ['storage/{path}', 'reset-password/{token}', 'up', 'test-sms', 'test-email'];

    public function test_every_page_renders_for_the_people_who_use_it(): void
    {
        Notification::fake();

        $company = Company::factory()->create(['name' => 'Smoke Homes']);
        $admin = PermissionsTeam::runAs($company->id, function () use ($company) {
            Role::findOrCreate('Admin', 'web')->syncPermissions(Permission::pluck('name'));
            $admin = User::factory()->create(['company_id' => $company->id]);
            $admin->assignRole('Admin');

            return $admin;
        });
        $owner = User::factory()->create(['company_id' => null]);

        $landlord = app(LandlordService::class)->create(['company_id' => $company->id, 'name' => 'Juma Landlord', 'phone' => '0754000222']);
        $property = Property::factory()->create(['company_id' => $company->id, 'landlord_id' => $landlord->id]);
        $building = Building::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);
        $unit = Unit::factory()->create(['company_id' => $company->id, 'building_id' => $building->id, 'status' => 'vacant']);
        $soldUnit = Unit::factory()->create(['company_id' => $company->id, 'building_id' => $building->id, 'status' => 'vacant']);
        $tenant = app(TenantService::class)->create(['company_id' => $company->id, 'name' => 'Amina Tenant', 'type' => 'individual', 'phone' => '0712000333']);
        $lease = app(LeaseService::class)->create([
            'company_id' => $company->id, 'tenant_id' => $tenant->id, 'unit_id' => $unit->id,
            'start_date' => now()->toDateString(), 'end_date' => now()->addYear()->toDateString(),
            'rent_amount' => 450000, 'deposit_amount' => 450000,
        ]);
        $buyer = Buyer::factory()->create(['company_id' => $company->id]);
        $sale = app(SaleService::class)->create([
            'company_id' => $company->id, 'unit_id' => $soldUnit->id, 'new_buyer_id' => $buyer->id,
            'sale_date' => now()->toDateString(), 'total_price' => 10000000, 'down_payment' => 2000000,
            'installment_period' => 4, 'installment_amount' => 2000000,
        ]);
        $account = Account::factory()->create(['company_id' => $company->id]);
        $vendor = Vendor::factory()->create(['company_id' => $company->id]);
        $tenantDoc = $tenant->documents()->save(Document::factory()->make(['company_id' => $company->id]));

        $models = [
            'account' => $account,
            'building' => $building,
            'buyer' => $buyer,
            'company' => $company,
            'document' => $tenantDoc,
            'expense' => Expense::factory()->create(['company_id' => $company->id, 'property_id' => $property->id, 'account_id' => $account->id]),
            'inventory_item' => InventoryItem::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]),
            'invoice' => Invoice::query()->where('lease_id', $lease->id)->firstOrFail(),
            'entry' => JournalEntry::factory()->create(['company_id' => $company->id]),
            'property' => $property,
            'landlord' => $landlord,
            'lease' => $lease,
            'maintenance_request' => MaintenanceRequest::factory()->create(['company_id' => $company->id, 'property_id' => $property->id, 'tenant_id' => $tenant->id, 'unit_id' => $unit->id]),
            'message_template' => MessageTemplate::query()->firstOrCreate(['company_id' => null, 'type' => 'tenant_welcome'], ['name' => 'Tenant Welcome', 'sms_template' => 'Hi {tenant_name}', 'email_subject' => 'Hi', 'email_template' => 'Hi', 'is_active' => true]),
            'role' => PermissionsTeam::runAs($company->id, fn () => Role::findByName('Admin', 'web')),
            'sale' => $sale,
            'installment' => $sale->installments()->first(),
            'subscription_plan' => SubscriptionPlan::factory()->create(),
            'tenant' => $tenant,
            'unit' => $unit,
            'user' => $admin,
            'vendor' => $vendor,
            'visitor' => Visitor::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]),
            'type' => 'storage',
            'provider' => 'local',
            'format' => 'csv',
        ];

        $tenant->forceFill(['password' => 'secret123'])->save();
        $landlord->forceFill(['password' => 'secret123'])->save();

        $failures = [];
        $visited = 0;

        foreach (Route::getRoutes() as $route) {
            /** @var RouteDefinition $route */
            $uri = $route->uri();
            if (! in_array('GET', $route->methods(), true) || str_starts_with($uri, 'api/') || str_starts_with($uri, '_') || in_array($uri, self::SKIP, true)) {
                continue;
            }
            if (! in_array('auth', $route->gatherMiddleware(), true) && ! str_contains(implode(',', $route->gatherMiddleware()), 'auth:')) {
                // Public pages (sign-in screens) - open them as a guest.
                $this->app['auth']->forgetGuards();
                $failures = [...$failures, ...$this->visit($route, $models, null)];
                $visited++;

                continue;
            }

            $actors = match (true) {
                str_starts_with($uri, 'portal/') => [['tenant', $tenant]],
                str_starts_with($uri, 'landlord-portal/') => [['landlord', $landlord]],
                default => [['web', $admin], ['web', $owner]],
            };

            foreach ($actors as [$guard, $actor]) {
                $this->app['auth']->forgetGuards();
                $failures = [...$failures, ...$this->visit($route, $models, [$guard, $actor])];
                $visited++;
            }
        }

        $this->assertGreaterThan(100, $visited, 'Fewer pages than expected were visited.');
        $this->assertSame([], $failures, "Pages that crashed:\n".implode("\n", $failures));
    }

    /**
     * @return array<int, string>
     */
    protected function visit(RouteDefinition $route, array $models, ?array $actor): array
    {
        $parameters = [];
        foreach ($route->parameterNames() as $name) {
            if (! array_key_exists($name, $models)) {
                return ["{$route->uri()}: no test value for {{$name}}"];
            }
            $parameters[$name] = $models[$name];
        }

        $url = url(preg_replace_callback('/\{(\w+)\??\}/', function ($m) use ($parameters) {
            $value = $parameters[$m[1]];

            return is_object($value) ? $value->getRouteKey() : $value;
        }, $route->uri()));

        $request = $actor ? $this->actingAs($actor[1], $actor[0]) : $this;
        $response = $request->get($url);

        $who = $actor ? ($actor[0] === 'web' ? ($actor[1]->company_id ? 'admin' : 'owner') : $actor[0]) : 'guest';

        if ($response->getStatusCode() >= 500) {
            $message = $response->exception?->getMessage() ?? 'HTTP '.$response->getStatusCode();

            return ["[{$who}] GET /{$route->uri()} -> {$response->getStatusCode()}: ".mb_substr($message, 0, 300)];
        }

        return [];
    }
}
