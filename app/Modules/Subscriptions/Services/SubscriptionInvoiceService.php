<?php

namespace App\Modules\Subscriptions\Services;

use App\Modules\Subscriptions\Models\Subscription;
use App\Modules\Subscriptions\Models\SubscriptionInvoice;
use Illuminate\Validation\ValidationException;

class SubscriptionInvoiceService
{
    /**
     * Bills the subscription's own plan price for its current period -
     * a manual, explicit SaaS-owner action (its own button, the same
     * "closing/opening action is deliberate, not automatic" discipline
     * as MaintenanceRequestService::recordExpense(), ARCHITECTURE.md
     * §15), not something that fires automatically on every subscribe()/
     * renew() call, since the SaaS owner may bill on their own schedule
     * independent of the subscription period itself.
     */
    public function generate(Subscription $subscription): SubscriptionInvoice
    {
        return SubscriptionInvoice::create([
            'company_id' => $subscription->company_id,
            'subscription_id' => $subscription->id,
            'invoice_number' => $this->nextInvoiceNumber(),
            'amount' => $subscription->plan->price,
            'status' => 'unpaid',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
        ]);
    }

    public function markPaid(SubscriptionInvoice $invoice): SubscriptionInvoice
    {
        if ($invoice->status !== 'unpaid') {
            throw ValidationException::withMessages(['status' => 'Only an unpaid invoice can be marked paid.']);
        }

        $invoice->update(['status' => 'paid', 'paid_at' => now()]);

        return $invoice->fresh();
    }

    public function void(SubscriptionInvoice $invoice): SubscriptionInvoice
    {
        if ($invoice->status === 'paid') {
            throw ValidationException::withMessages(['status' => 'A paid invoice cannot be voided - it is a settled record.']);
        }

        $invoice->update(['status' => 'void']);

        return $invoice->fresh();
    }

    /**
     * Globally sequenced, not per-company - the same reasoning
     * subscription_invoices.invoice_number's migration comment gives:
     * this is the SaaS owner's own numbering, a different sequence
     * entirely from Invoice::generateInvoiceNumber()'s per-company one.
     */
    protected function nextInvoiceNumber(): string
    {
        $count = SubscriptionInvoice::withTrashed()->count();

        return 'SUB-'.str_pad((string) ($count + 1), 6, '0', STR_PAD_LEFT);
    }
}
