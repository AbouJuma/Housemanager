<?php

namespace App\Modules\Reports\Services;

use App\Modules\Invoices\Models\Invoice;
use Illuminate\Support\Carbon;

class ArAgingReportService
{
    /** Invoice statuses that can still be owed - and filtered by. */
    public const STATUSES = ['sent', 'partially_paid', 'overdue'];

    protected const BUCKETS = ['current', '1_30', '31_60', '61_90', 'over_90'];

    public function generate(?string $status = null): array
    {
        $today = Carbon::today();

        $buckets = array_fill_keys(self::BUCKETS, []);

        $invoices = Invoice::query()
            ->whereIn('status', in_array($status, self::STATUSES, true) ? [$status] : self::STATUSES)
            ->with(['lease.tenant', 'sale.buyer'])
            ->get()
            ->filter(fn (Invoice $invoice) => $invoice->balanceDue() > 0);

        foreach ($invoices as $invoice) {
            $daysOverdue = $invoice->due_date->isPast() ? $invoice->due_date->diffInDays($today) : 0;

            $bucket = match (true) {
                $daysOverdue === 0 => 'current',
                $daysOverdue <= 30 => '1_30',
                $daysOverdue <= 60 => '31_60',
                $daysOverdue <= 90 => '61_90',
                default => 'over_90',
            };

            $buckets[$bucket][] = [
                'invoice_number' => $invoice->invoice_number,
                // Rent invoices are owed by the lease's tenant, sale invoices
                // by the buyer - a sale invoice has no lease at all.
                'tenant' => $invoice->lease?->tenant?->name ?? $invoice->sale?->buyer?->name ?? 'Unknown customer',
                'due_date' => $invoice->due_date->toDateString(),
                'days_overdue' => $daysOverdue,
                'balance' => $invoice->balanceDue(),
            ];
        }

        // (float) cast - Collection::sum() returns int 0, not float 0.0,
        // for an empty collection (nothing to infer float-ness from),
        // which would otherwise make an all-current-invoices report's
        // "empty bucket" totals silently inconsistent in type with every
        // non-empty bucket's float sum.
        $totals = collect($buckets)->map(fn ($rows) => (float) collect($rows)->sum('balance'));

        return [
            'buckets' => $buckets,
            'totals' => $totals,
            'grand_total' => (float) $totals->sum(),
        ];
    }
}
