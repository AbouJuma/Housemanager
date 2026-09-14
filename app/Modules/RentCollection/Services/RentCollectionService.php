<?php

namespace App\Modules\RentCollection\Services;

use App\Modules\Companies\Models\Company;
use App\Modules\Integrations\Models\Integration;
use App\Modules\Invoices\Models\Invoice;
use App\Modules\Invoices\Services\InvoiceService;
use App\Modules\Payments\Services\PaymentManager;
use App\Modules\RentCollection\Models\InvoicePaymentAttempt;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * The Rent Collection module's whole job, per ARCHITECTURE.md §9's
 * roadmap phrasing, is to *consume* Payments (Module 11) and Invoices
 * (Module 17) rather than extend either - this service is the only
 * place in the app that converts an Invoice's decimal balance into the
 * integer-minor-units PaymentGatewayInterface expects (the exact
 * integration boundary Module 13's ARCHITECTURE.md note pointed to),
 * and the only place that calls InvoiceService::recordPayment() with
 * method 'gateway'.
 */
class RentCollectionService
{
    public function __construct(
        protected PaymentManager $paymentManager,
        protected InvoiceService $invoiceService,
    ) {
    }

    /**
     * Initiates an online charge for (up to) an invoice's balance due.
     * Many gateways confirm asynchronously (a redirect-based checkout,
     * an STK push the tenant approves on their phone) - charge() only
     * ever creates a 'pending' or 'failed' attempt; a 'successful' one
     * is only ever reached via verifyAttempt(), never here, even if the
     * driver happens to report success immediately, since every real
     * driver in this app's catalog reports charge()'s success as a
     * "request accepted", not "money received" (see StripeDriver's and
     * MpesaDriver's docblocks).
     */
    public function chargeInvoice(Invoice $invoice, float $amount, ?User $initiator = null): InvoicePaymentAttempt
    {
        if (! in_array($invoice->status, ['sent', 'partially_paid', 'overdue'], true)) {
            throw ValidationException::withMessages(['status' => 'Online payment can only be collected against a sent invoice.']);
        }

        if ($amount <= 0 || $amount > $invoice->balanceDue()) {
            throw ValidationException::withMessages(['amount' => 'Amount must be greater than zero and not exceed the balance due.']);
        }

        $company = Company::query()->findOrFail($invoice->company_id);

        $integration = Integration::query()
            ->forCompany($company->id)
            ->where('type', 'payment')
            ->where('is_active', true)
            ->first();

        if (! $integration) {
            throw ValidationException::withMessages(['provider' => 'No active payment provider is configured for this company.']);
        }

        $amountMinor = (int) round($amount * 100);
        $reference = 'INV-'.$invoice->invoice_number.'-'.Str::upper(Str::random(6));

        $result = $this->paymentManager->charge($company, $amountMinor, $company->currency_code, $reference, [
            'description' => "Invoice {$invoice->invoice_number}",
        ]);

        $attempt = InvoicePaymentAttempt::create([
            'company_id' => $invoice->company_id,
            'invoice_id' => $invoice->id,
            'provider' => $integration->provider,
            'reference' => $result['reference'] ?? $reference,
            'amount_minor' => $amountMinor,
            'currency' => $company->currency_code,
            'status' => $result['success'] ? 'pending' : 'failed',
            'message' => $result['message'] ?? null,
            'initiated_by_id' => $initiator?->id,
            'meta' => $result,
        ]);

        return $attempt;
    }

    /**
     * Re-checks a pending attempt against the gateway's own status API
     * (PaymentGatewayInterface::verify()) rather than trusting anything
     * a caller supplies - this is the single authoritative path to
     * 'successful', called both by the scheduled
     * VerifyPendingRentPayments sweep and by the inbound webhook
     * receiver (which only uses the webhook payload to know WHICH
     * attempt to re-check, never to decide the outcome itself - see
     * ARCHITECTURE.md §10f).
     */
    public function verifyAttempt(InvoicePaymentAttempt $attempt): InvoicePaymentAttempt
    {
        if (! $attempt->isPending() || $attempt->reference === null) {
            return $attempt;
        }

        $company = Company::query()->findOrFail($attempt->company_id);

        $result = $this->paymentManager->verify($company, $attempt->provider, $attempt->reference);

        $attempt->update(['verified_at' => now(), 'message' => $result['message'] ?? $attempt->message]);

        if ($result['success']) {
            $this->markSuccessful($attempt, $result);
        }

        return $attempt->fresh();
    }

    /**
     * Attempts stuck pending for over 24 hours are treated as
     * abandoned, not failed by the gateway - verify()'s `success: false`
     * doesn't distinguish "still processing" from "the customer
     * cancelled", so this is a pragmatic timeout, not a gateway-
     * confirmed outcome (see ARCHITECTURE.md §10f). A later
     * verifyAttempt() call can never resurrect an expired attempt; the
     * tenant would need a fresh chargeInvoice() call instead.
     */
    public function expireStaleAttempts(): int
    {
        return InvoicePaymentAttempt::query()
            ->where('status', 'pending')
            ->where('created_at', '<', now()->subDay())
            ->update(['status' => 'failed', 'message' => 'Payment attempt expired without confirmation.']);
    }

    protected function markSuccessful(InvoicePaymentAttempt $attempt, array $result): void
    {
        DB::transaction(function () use ($attempt, $result): void {
            $attempt->update(['status' => 'successful']);

            $invoice = $attempt->invoice;

            $this->invoiceService->recordPayment($invoice, [
                'amount' => $attempt->amountMajor(),
                'paid_on' => now()->toDateString(),
                'method' => 'gateway',
                'reference' => $attempt->reference,
                'notes' => "Collected via {$attempt->provider}.",
                'recorded_by_id' => $attempt->initiated_by_id,
            ]);
        });
    }
}
