<?php

namespace App\Modules\Subscriptions\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Subscriptions\Http\Requests\StoreSubscriptionPlanRequest;
use App\Modules\Subscriptions\Http\Requests\UpdateSubscriptionPlanRequest;
use App\Modules\Subscriptions\Models\SubscriptionPlan;
use App\Modules\Subscriptions\Services\SubscriptionOverviewService;
use App\Modules\Subscriptions\Services\SubscriptionPlanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionPlanController extends Controller
{
    public function __construct(
        protected SubscriptionPlanService $service,
        protected SubscriptionOverviewService $overview,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', SubscriptionPlan::class);

        return view('subscriptions::plans.index', [
            'plans' => $this->service->paginate($request->string('search')->toString() ?: null),
            'overview' => $this->overview->summarize(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', SubscriptionPlan::class);

        return view('subscriptions::plans.create');
    }

    public function store(StoreSubscriptionPlanRequest $request): RedirectResponse
    {
        $plan = $this->service->create($request->validated());

        return redirect()->route('subscription-plans.show', $plan)->with('success', 'Subscription plan created.');
    }

    public function show(SubscriptionPlan $subscriptionPlan): View
    {
        $this->authorize('view', $subscriptionPlan);

        return view('subscriptions::plans.show', [
            'plan' => $subscriptionPlan,
            'subscriberCount' => $subscriptionPlan->subscriptions()->distinct('company_id')->count('company_id'),
        ]);
    }

    public function edit(SubscriptionPlan $subscriptionPlan): View
    {
        $this->authorize('update', $subscriptionPlan);

        return view('subscriptions::plans.edit', ['plan' => $subscriptionPlan]);
    }

    public function update(UpdateSubscriptionPlanRequest $request, SubscriptionPlan $subscriptionPlan): RedirectResponse
    {
        $this->service->update($subscriptionPlan, $request->validated());

        return redirect()->route('subscription-plans.show', $subscriptionPlan)->with('success', 'Subscription plan updated.');
    }

    public function destroy(SubscriptionPlan $subscriptionPlan): RedirectResponse
    {
        $this->authorize('delete', $subscriptionPlan);

        $this->service->delete($subscriptionPlan);

        return redirect()->route('subscription-plans.index')->with('success', 'Subscription plan deleted.');
    }
}
