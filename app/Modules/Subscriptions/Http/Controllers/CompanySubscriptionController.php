<?php

namespace App\Modules\Subscriptions\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Companies\Models\Company;
use App\Modules\Subscriptions\Http\Requests\ChangeCompanyPlanRequest;
use App\Modules\Subscriptions\Http\Requests\ExtendTrialRequest;
use App\Modules\Subscriptions\Models\Subscription;
use App\Modules\Subscriptions\Models\SubscriptionPlan;
use App\Modules\Subscriptions\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * SaaS-owner-side subscription state changes, wired into Company's own
 * show page (Module 2) - the same "actions live where the record is
 * already displayed" convention as the Tenant/Landlord Portal Access
 * cards (Modules 26/27).
 */
class CompanySubscriptionController extends Controller
{
    public function __construct(protected SubscriptionService $service)
    {
    }

    public function changePlan(ChangeCompanyPlanRequest $request, Company $company): RedirectResponse
    {
        $plan = SubscriptionPlan::findOrFail($request->validated('subscription_plan_id'));
        $current = $company->currentSubscription();

        if ($current && $current->isActive()) {
            $this->service->changePlan($company, $plan);
        } else {
            $this->service->subscribe($company, $plan, $request->validated('status'));
        }

        return redirect()->route('companies.show', $company)->with('success', 'Subscription updated.');
    }

    public function extendTrial(ExtendTrialRequest $request, Subscription $subscription): RedirectResponse
    {
        $this->service->extendTrial($subscription, (int) $request->validated('days'));

        return redirect()->route('companies.show', $subscription->company)->with('success', 'Trial extended.');
    }

    public function renew(Subscription $subscription): RedirectResponse
    {
        $this->authorize('update', $subscription);

        $this->service->renew($subscription);

        return redirect()->route('companies.show', $subscription->company)->with('success', 'Subscription renewed.');
    }

    public function cancel(Subscription $subscription): RedirectResponse
    {
        $this->authorize('update', $subscription);

        $this->service->cancel($subscription);

        return redirect()->route('companies.show', $subscription->company)->with('success', 'Subscription canceled.');
    }

    /**
     * Deliberately gated on `isSaasOwner()` directly, not CompanyPolicy's
     * own `update` ability - that ability also allows a company's own
     * admin to manage their company, but a suspended/cancelled company
     * un-suspending itself would defeat the entire point of Module 29's
     * enforcement (EnsureCompanyIsActive, ARCHITECTURE.md §22).
     */
    public function reactivate(Request $request, Company $company): RedirectResponse
    {
        abort_unless($request->user()->isSaasOwner(), 403);

        $this->service->reactivate($company);

        return redirect()->route('companies.show', $company)->with('success', 'Company reactivated.');
    }
}
