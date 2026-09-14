<?php

namespace App\Modules\Subscriptions\Services;

use App\Modules\Companies\Models\Company;
use App\Modules\Subscriptions\Models\Subscription;
use App\Modules\Subscriptions\Models\SubscriptionPlan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * State transitions on Subscription/Company, not a BaseService CRUD
 * pair - there's no generic "update a subscription" action, only these
 * specific, named business operations (see Subscription's own class doc
 * for why changing plans creates a new row rather than mutating one).
 * `Company.status` is kept in lockstep with the subscription's own
 * status by every method here - App\Modules\Subscriptions\Http\
 * Middleware\EnsureCompanyIsActive reads company.status as the single
 * source of truth, so it must never drift from the subscription that's
 * supposed to be driving it.
 */
class SubscriptionService
{
    public function subscribe(Company $company, SubscriptionPlan $plan, string $status = 'trialing', ?int $trialDays = 14): Subscription
    {
        return DB::transaction(function () use ($company, $plan, $status, $trialDays) {
            $periodStart = Carbon::today();
            $periodEnd = $status === 'trialing'
                ? $periodStart->copy()->addDays($trialDays)
                : $this->nextPeriodEnd($plan, $periodStart);

            $subscription = $company->subscriptions()->create([
                'subscription_plan_id' => $plan->id,
                'status' => $status,
                'current_period_start' => $periodStart,
                'current_period_end' => $periodEnd,
            ]);

            $company->update([
                'status' => $status === 'trialing' ? 'trial' : 'active',
                'trial_ends_at' => $status === 'trialing' ? $periodEnd : null,
            ]);

            return $subscription;
        });
    }

    /**
     * A plan change is immediate, not another trial - always creates a
     * new 'active' period starting now. The superseded row is left
     * alone rather than marked canceled; it wasn't canceled, it was
     * superseded, and Company::currentSubscription() already resolves
     * to whichever row has the latest current_period_start.
     */
    public function changePlan(Company $company, SubscriptionPlan $plan): Subscription
    {
        return DB::transaction(function () use ($company, $plan) {
            $periodStart = Carbon::today();

            $subscription = $company->subscriptions()->create([
                'subscription_plan_id' => $plan->id,
                'status' => 'active',
                'current_period_start' => $periodStart,
                'current_period_end' => $this->nextPeriodEnd($plan, $periodStart),
            ]);

            $company->update(['status' => 'active', 'trial_ends_at' => null]);

            return $subscription;
        });
    }

    public function renew(Subscription $subscription): Subscription
    {
        $company = $subscription->company;
        $plan = $subscription->plan;
        $periodStart = $subscription->current_period_end->isFuture() ? $subscription->current_period_end : Carbon::today();

        return DB::transaction(function () use ($company, $plan, $periodStart) {
            $new = $company->subscriptions()->create([
                'subscription_plan_id' => $plan->id,
                'status' => 'active',
                'current_period_start' => $periodStart,
                'current_period_end' => $this->nextPeriodEnd($plan, $periodStart),
            ]);

            $company->update(['status' => 'active', 'trial_ends_at' => null]);

            return $new;
        });
    }

    public function extendTrial(Subscription $subscription, int $days): Subscription
    {
        if ($subscription->status !== 'trialing') {
            throw ValidationException::withMessages(['status' => 'Only a trialing subscription can have its trial extended.']);
        }

        $newEnd = $subscription->current_period_end->copy()->addDays($days);

        $subscription->update(['current_period_end' => $newEnd]);
        $subscription->company->update(['trial_ends_at' => $newEnd]);

        return $subscription->fresh();
    }

    public function cancel(Subscription $subscription): Subscription
    {
        $subscription->update(['status' => 'canceled', 'canceled_at' => now()]);
        $subscription->company->update(['status' => 'cancelled']);

        return $subscription->fresh();
    }

    /**
     * Reactivating after a suspension/cancellation doesn't need a new
     * subscription row if the existing one's period hasn't actually
     * lapsed - just restores company.status so access resumes.
     */
    public function reactivate(Company $company): void
    {
        $subscription = $company->currentSubscription();

        if (! $subscription) {
            throw ValidationException::withMessages(['status' => 'This company has no subscription to reactivate.']);
        }

        $company->update(['status' => $subscription->status === 'trialing' ? 'trial' : 'active']);
    }

    protected function nextPeriodEnd(SubscriptionPlan $plan, Carbon $from): Carbon
    {
        return $plan->billing_cycle === 'yearly' ? $from->copy()->addYear() : $from->copy()->addMonth();
    }
}
