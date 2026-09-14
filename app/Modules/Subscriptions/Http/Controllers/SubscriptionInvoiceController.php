<?php

namespace App\Modules\Subscriptions\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Subscriptions\Models\Subscription;
use App\Modules\Subscriptions\Models\SubscriptionInvoice;
use App\Modules\Subscriptions\Services\SubscriptionInvoiceService;
use Illuminate\Http\RedirectResponse;

class SubscriptionInvoiceController extends Controller
{
    public function __construct(protected SubscriptionInvoiceService $service)
    {
    }

    public function generate(Subscription $subscription): RedirectResponse
    {
        $this->authorize('create', SubscriptionInvoice::class);

        $this->service->generate($subscription);

        return redirect()->route('companies.show', $subscription->company)->with('success', 'Invoice generated.');
    }

    public function markPaid(SubscriptionInvoice $subscriptionInvoice): RedirectResponse
    {
        $this->authorize('update', $subscriptionInvoice);

        $this->service->markPaid($subscriptionInvoice);

        return redirect()->route('companies.show', $subscriptionInvoice->company)->with('success', 'Invoice marked paid.');
    }

    public function void(SubscriptionInvoice $subscriptionInvoice): RedirectResponse
    {
        $this->authorize('update', $subscriptionInvoice);

        $this->service->void($subscriptionInvoice);

        return redirect()->route('companies.show', $subscriptionInvoice->company)->with('success', 'Invoice voided.');
    }
}
