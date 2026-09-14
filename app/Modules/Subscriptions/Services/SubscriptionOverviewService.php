<?php

namespace App\Modules\Subscriptions\Services;

use App\Modules\Subscriptions\Models\Subscription;
use App\Modules\Subscriptions\Models\SubscriptionInvoice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * A pure, read-only aggregation over data this module already owns - the
 * same "single-method service class, no repository, no Policy" shape
 * Module 28's report services established (ARCHITECTURE.md §21), reused
 * here by both the SaaS-owner-facing Subscription Plans page (the
 * per-plan/expiring-soon detail) and the Dashboard's SaaS-owner widgets
 * (a subset of the same numbers) - one query source of truth, not two.
 */
class SubscriptionOverviewService
{
    /**
     * How many days ahead counts as "expiring soon" - matches the
     * "New Companies (Last 14 Days)" window the Dashboard already uses
     * elsewhere, for a consistent sense of time scale across the app.
     */
    protected const EXPIRING_SOON_DAYS = 14;

    public function summarize(): array
    {
        $current = Subscription::query()->current();

        $activeSubscriptionsCount = (clone $current)->where('status', 'active')->count();
        $trialingSubscriptionsCount = (clone $current)->where('status', 'trialing')->count();

        $expiringSoonQuery = (clone $current)
            ->whereIn('status', ['trialing', 'active'])
            ->whereDate('current_period_end', '<=', now()->addDays(self::EXPIRING_SOON_DAYS));

        $unpaidInvoices = SubscriptionInvoice::query()->where('status', 'unpaid');
        $overdueInvoices = (clone $unpaidInvoices)->whereDate('due_date', '<', now()->toDateString());

        return [
            'activeSubscriptionsCount' => $activeSubscriptionsCount,
            'trialingSubscriptionsCount' => $trialingSubscriptionsCount,
            'mrr' => $this->monthlyRecurringRevenue(clone $current),
            'subscribersByPlan' => $this->subscribersByPlan(clone $current),
            'expiringSoonCount' => (clone $expiringSoonQuery)->count(),
            'expiringSoon' => (clone $expiringSoonQuery)
                ->with(['company', 'plan'])
                ->orderBy('current_period_end')
                ->limit(10)
                ->get(),
            'unpaidInvoicesCount' => (clone $unpaidInvoices)->count(),
            'unpaidInvoicesTotal' => (float) (clone $unpaidInvoices)->sum('amount'),
            'overdueInvoicesCount' => (clone $overdueInvoices)->count(),
            'overdueInvoicesTotal' => (float) (clone $overdueInvoices)->sum('amount'),
        ];
    }

    /**
     * Every active subscription's plan price, yearly plans normalized to
     * a monthly figure - the standard SaaS "MRR" definition.
     */
    protected function monthlyRecurringRevenue(Builder $current): float
    {
        return $current->where('status', 'active')
            ->join('subscription_plans', 'subscription_plans.id', '=', 'subscriptions.subscription_plan_id')
            ->get(['subscription_plans.price', 'subscription_plans.billing_cycle'])
            ->sum(fn ($row) => $row->billing_cycle === 'yearly' ? $row->price / 12 : (float) $row->price);
    }

    /**
     * @return Collection<int, int> subscription_plan_id => count of
     *  companies currently on it (trialing or active - a canceled or
     *  past_due row shouldn't inflate a plan's apparent popularity)
     */
    protected function subscribersByPlan(Builder $current): Collection
    {
        return $current->whereIn('status', ['trialing', 'active'])
            ->selectRaw('subscription_plan_id, count(*) as count')
            ->groupBy('subscription_plan_id')
            ->pluck('count', 'subscription_plan_id');
    }
}
