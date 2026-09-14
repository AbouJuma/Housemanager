<?php

namespace Tests\Feature\Subscriptions;

use App\Modules\Companies\Models\Company;
use App\Modules\Subscriptions\Models\SubscriptionPlan;
use App\Modules\Subscriptions\Services\SubscriptionInvoiceService;
use App\Modules\Subscriptions\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SubscriptionInvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SubscriptionInvoiceService $invoices;

    protected SubscriptionService $subscriptions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->invoices = app(SubscriptionInvoiceService::class);
        $this->subscriptions = app(SubscriptionService::class);
    }

    protected function makeSubscription(float $price = 79.0)
    {
        $company = Company::factory()->create();
        $plan = SubscriptionPlan::factory()->create(['price' => $price]);

        return $this->subscriptions->subscribe($company, $plan, 'active');
    }

    public function test_generating_an_invoice_bills_the_plans_current_price(): void
    {
        $subscription = $this->makeSubscription(79.0);

        $invoice = $this->invoices->generate($subscription);

        $this->assertSame($subscription->company_id, $invoice->company_id);
        $this->assertSame($subscription->id, $invoice->subscription_id);
        $this->assertSame('unpaid', $invoice->status);
        $this->assertSame('79.00', (string) $invoice->amount);
    }

    public function test_invoice_numbers_are_sequential_and_globally_unique(): void
    {
        $first = $this->invoices->generate($this->makeSubscription());
        $second = $this->invoices->generate($this->makeSubscription());

        $this->assertNotSame($first->invoice_number, $second->invoice_number);
        $this->assertStringStartsWith('SUB-', $first->invoice_number);
    }

    public function test_marking_an_invoice_paid_stamps_paid_at(): void
    {
        $invoice = $this->invoices->generate($this->makeSubscription());

        $paid = $this->invoices->markPaid($invoice);

        $this->assertSame('paid', $paid->status);
        $this->assertNotNull($paid->paid_at);
    }

    public function test_a_paid_invoice_cannot_be_marked_paid_again(): void
    {
        $invoice = $this->invoices->generate($this->makeSubscription());
        $this->invoices->markPaid($invoice);

        $this->expectException(ValidationException::class);

        $this->invoices->markPaid($invoice->fresh());
    }

    public function test_voiding_an_unpaid_invoice_marks_it_void(): void
    {
        $invoice = $this->invoices->generate($this->makeSubscription());

        $voided = $this->invoices->void($invoice);

        $this->assertSame('void', $voided->status);
    }

    public function test_a_paid_invoice_cannot_be_voided(): void
    {
        $invoice = $this->invoices->generate($this->makeSubscription());
        $this->invoices->markPaid($invoice);

        $this->expectException(ValidationException::class);

        $this->invoices->void($invoice->fresh());
    }
}
