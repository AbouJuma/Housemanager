<?php

namespace App\Modules\Reports\Services;

use App\Modules\Subscriptions\Models\Subscription;
use App\Modules\Subscriptions\Models\SubscriptionInvoice;
use Illuminate\Support\Collection;

class SubscriptionStatusReportService
{
    public function generate(): Collection
    {
        $subscriptions = Subscription::query()
            ->with(['company', 'plan'])
            ->current()
            ->orderBy('status')
            ->orderBy('current_period_end', 'desc')
            ->get();

        $unpaidCounts = SubscriptionInvoice::query()
            ->where('status', 'unpaid')
            ->groupBy('company_id')
            ->selectRaw('company_id, count(*) as count')
            ->pluck('count', 'company_id');

        $overdueCounts = SubscriptionInvoice::query()
            ->where('status', 'unpaid')
            ->whereDate('due_date', '<', now()->toDateString())
            ->groupBy('company_id')
            ->selectRaw('company_id, count(*) as count')
            ->pluck('count', 'company_id');

        return $subscriptions->map(function (Subscription $subscription) use ($unpaidCounts, $overdueCounts) {
            return [
                'company' => $subscription->company->name,
                'plan' => $subscription->plan->name,
                'status' => $subscription->status,
                'current_period' => $subscription->current_period_start->format('M j, Y').' – '.$subscription->current_period_end->format('M j, Y'),
                'unpaid_invoices' => $unpaidCounts->get($subscription->company_id, 0),
                'overdue_invoices' => $overdueCounts->get($subscription->company_id, 0),
            ];
        });
    }
}
