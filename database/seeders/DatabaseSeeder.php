<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Accounting\Database\Seeders\JournalEntrySeeder;
use App\Modules\Companies\Database\Seeders\CompanySeeder;
use App\Modules\Documents\Database\Seeders\DocumentSeeder;
use App\Modules\Expenses\Database\Seeders\ExpenseSeeder;
use App\Modules\Inventory\Database\Seeders\InventoryItemSeeder;
use App\Modules\Invoices\Database\Seeders\InvoiceSeeder;
use App\Modules\Landlords\Database\Seeders\LandlordSeeder;
use App\Modules\Leases\Database\Seeders\LeaseSeeder;
use App\Modules\Maintenance\Database\Seeders\MaintenanceRequestSeeder;
use App\Modules\Properties\Database\Seeders\PropertySeeder;
use App\Modules\RentCollection\Database\Seeders\RentCollectionSeeder;
use App\Modules\Subscriptions\Database\Seeders\SubscriptionInvoiceSeeder;
use App\Modules\Subscriptions\Database\Seeders\SubscriptionPlanSeeder;
use App\Modules\Tenants\Database\Seeders\TenantSeeder;
use App\Modules\LandlordPortal\Database\Seeders\LandlordPortalSeeder;
use App\Modules\TenantPortal\Database\Seeders\TenantPortalSeeder;
use App\Modules\Users\Database\Seeders\PermissionSeeder;
use App\Modules\Users\Database\Seeders\UserSeeder;
use App\Modules\Vendors\Database\Seeders\VendorSeeder;
use App\Modules\VisitorManagement\Database\Seeders\VisitorSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Model `creating` events are intentionally left enabled (no
     * WithoutModelEvents) - App\Core\Concerns\HasUuid/BelongsToCompany
     * rely on them to stamp uuid/company_id, so disabling events during
     * seeding would silently produce invalid rows.
     */
    public function run(): void
    {
        // SaaS-owner staff account: null company_id, per the tenancy model in docs/ARCHITECTURE.md.
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Must run before CompanySeeder: SeedDefaultRolesForCompany (fired by
        // each company's creation) assigns every existing Permission to that
        // company's new Admin role, so the catalog needs to exist first.
        $this->call(PermissionSeeder::class);

        // Must run before CompanySeeder: SeedDefaultTrialSubscription
        // (fired by every company's CompanyCreated event) needs at least
        // one active plan to exist to put a new company on trial with.
        $this->call(SubscriptionPlanSeeder::class);

        $this->call(CompanySeeder::class);

        // Each seeded company already has its default chart of accounts
        // by this point too - CompanySeeder dispatches CompanyCreated for
        // every company it makes, which SeedDefaultChartOfAccountsForCompany
        // (Accounting module) listens for, the same way it triggers
        // SeedDefaultRolesForCompany above.
        $this->call(JournalEntrySeeder::class);

        // Each seeded company gets one Admin-role user, so local dev has a
        // ready-made "log in as a company admin" account per company.
        $this->call(UserSeeder::class);

        $this->call(PropertySeeder::class);

        // Must run after CompanySeeder (needs a company to belong to).
        $this->call(VendorSeeder::class);

        // Must run after PropertySeeder (needs properties), VendorSeeder
        // (optionally links a vendor), and CompanySeeder/JournalEntrySeeder
        // (needs the default chart of accounts, already seeded by then
        // via CompanyCreated).
        $this->call(ExpenseSeeder::class);

        // Must run after PropertySeeder: assigns landlords to existing
        // seeded properties, so the properties need to exist first.
        $this->call(LandlordSeeder::class);

        $this->call(TenantSeeder::class);

        // Must run after both PropertySeeder (needs units) and
        // TenantSeeder (needs tenants) - pairs the two together.
        $this->call(LeaseSeeder::class);

        // Must run after LeaseSeeder - invoices are billed against leases.
        $this->call(InvoiceSeeder::class);

        // Must run after InvoiceSeeder - payment attempts are recorded against invoices.
        $this->call(RentCollectionSeeder::class);

        // Must run after PropertySeeder/TenantSeeder/VendorSeeder - a
        // request can reference a unit, tenant, or vendor.
        $this->call(MaintenanceRequestSeeder::class);

        // Must run after PropertySeeder/VendorSeeder/MaintenanceRequestSeeder
        // - an item can reference a property or vendor, and a `usage`
        // transaction can optionally link back to an existing request.
        $this->call(InventoryItemSeeder::class);

        // Must run after PropertySeeder/LandlordSeeder/TenantSeeder/
        // LeaseSeeder/VendorSeeder - a document can attach to any one of
        // the five.
        $this->call(DocumentSeeder::class);

        // Must run after PropertySeeder/TenantSeeder/VendorSeeder - a
        // visit can optionally reference a tenant or vendor.
        $this->call(VisitorSeeder::class);

        // Must run after TenantSeeder/LeaseSeeder - needs a tenant with
        // an active lease to enable a ready-made portal login for.
        $this->call(TenantPortalSeeder::class);

        // Must run after LandlordSeeder/PropertySeeder - needs a
        // landlord with at least one property to enable a ready-made
        // portal login for.
        $this->call(LandlordPortalSeeder::class);

        // Must run after CompanySeeder - bills each company's own
        // CompanyCreated-triggered subscription for its first period.
        $this->call(SubscriptionInvoiceSeeder::class);
    }
}
