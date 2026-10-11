<?php

namespace Tests\Feature\RentCollection;

use App\Modules\Companies\Models\Company;
use App\Modules\Invoices\Models\Invoice;
use App\Modules\Leases\Models\Lease;
use App\Modules\PaymentReminders\Services\ReminderRuleService;
use App\Modules\Properties\Models\Building;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use App\Modules\RentCollection\Notifications\InvoiceDueSoon;
use App\Modules\RentCollection\Notifications\InvoiceOverdue;
use App\Modules\Tenants\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendRentRemindersTest extends TestCase
{
    use RefreshDatabase;

    protected function makeInvoice(string $status, array $overrides = []): Invoice
    {
        $company = Company::factory()->create();

        // Company::factory()->create() is direct Eloquent creation - it
        // never fires CompanyCreated (see CompanySeeder's own docblock),
        // so this company gets no reminder rules automatically the way
        // a company created through CompanyService::create() would.
        // Seeding the same defaults SeedDefaultReminderRulesForCompany
        // would apply keeps these tests' due-date math (3 days
        // due-soon, 7-day overdue repeat) meaningful.
        app(ReminderRuleService::class)->seedDefaultsFor($company);

        $tenant = Tenant::factory()->create(['company_id' => $company->id]);
        $property = Property::factory()->create(['company_id' => $company->id]);
        $building = Building::factory()->create(['company_id' => $company->id, 'property_id' => $property->id]);
        $unit = Unit::factory()->create(['company_id' => $company->id, 'building_id' => $building->id, 'status' => 'occupied']);
        $lease = Lease::factory()->create(['company_id' => $company->id, 'tenant_id' => $tenant->id, 'unit_id' => $unit->id]);

        return Invoice::factory()->create(array_merge([
            'company_id' => $company->id,
            'lease_id' => $lease->id,
            'status' => $status,
        ], $overrides));
    }

    public function test_it_notifies_tenants_whose_invoice_is_due_in_exactly_three_days(): void
    {
        Notification::fake();

        $dueSoon = $this->makeInvoice('sent', ['due_date' => now()->addDays(3)->toDateString()]);
        $dueLater = $this->makeInvoice('sent', ['due_date' => now()->addDays(10)->toDateString()]);
        $draft = $this->makeInvoice('draft', ['due_date' => now()->addDays(3)->toDateString()]);

        $this->artisan('rent:send-reminders')->assertSuccessful();

        Notification::assertSentTo($dueSoon->lease->tenant, InvoiceDueSoon::class);
        Notification::assertNotSentTo($dueLater->lease->tenant, InvoiceDueSoon::class);
        Notification::assertNotSentTo($draft->lease->tenant, InvoiceDueSoon::class);
    }

    public function test_it_does_not_re_notify_the_same_due_soon_invoice_on_a_second_run(): void
    {
        // Mail::fake(), not Notification::fake() - the dedup check
        // queries the real notifications table (the 'database' channel),
        // so faking the whole Notification facade would prevent that
        // row from ever being written and defeat the test (same
        // reasoning as NotifyTrialsEndingSoonTest's "running it twice"
        // case).
        Mail::fake();

        $invoice = $this->makeInvoice('sent', ['due_date' => now()->addDays(3)->toDateString()]);

        $this->artisan('rent:send-reminders');
        $this->artisan('rent:send-reminders');

        $this->assertSame(1, $invoice->lease->tenant->notifications()->where('type', InvoiceDueSoon::class)->count());
    }

    public function test_it_notifies_tenants_of_overdue_invoices(): void
    {
        Notification::fake();

        $overdue = $this->makeInvoice('overdue', ['due_date' => now()->subDays(5)->toDateString()]);

        $this->artisan('rent:send-reminders')->assertSuccessful();

        Notification::assertSentTo($overdue->lease->tenant, InvoiceOverdue::class);
    }

    public function test_it_does_not_re_notify_an_overdue_invoice_within_seven_days(): void
    {
        Mail::fake();

        $invoice = $this->makeInvoice('overdue', ['due_date' => now()->subDays(5)->toDateString()]);

        $this->artisan('rent:send-reminders');
        $this->artisan('rent:send-reminders');

        $this->assertSame(1, $invoice->lease->tenant->notifications()->where('type', InvoiceOverdue::class)->count());
    }
}
