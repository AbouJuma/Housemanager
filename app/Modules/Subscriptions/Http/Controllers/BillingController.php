<?php

namespace App\Modules\Subscriptions\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Subscriptions\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Company-facing, read-only - the counterpart to the SaaS-owner-side
 * actions in CompanySubscriptionController/SubscriptionInvoiceController.
 * A company can see its own plan and billing history but never change
 * anything from here (see SubscriptionPolicy/SubscriptionInvoicePolicy's
 * `view`-only-for-non-SaaS-owner shape, ARCHITECTURE.md §22).
 */
class BillingController extends Controller
{
    public function index(Request $request): View
    {
        $company = $request->user()->company;
        abort_if($company === null, 403);

        $subscription = $company->currentSubscription();
        $this->authorize('view', $subscription ?? new Subscription(['company_id' => $company->id]));

        return view('subscriptions::billing', [
            'company' => $company,
            'subscription' => $subscription,
            'invoices' => $company->subscriptionInvoices()->latest('issue_date')->get(),
        ]);
    }
}
