<?php

namespace App\Modules\Reports\Services;

use App\Modules\Subscriptions\Models\SubscriptionInvoice;
use Illuminate\Support\Collection;

class BillingStatusReportService
{
    public function generate(): Collection
    {
        return SubscriptionInvoice::query()
            ->with('company')
            ->orderBy('due_date', 'desc')
            ->orderBy('issue_date', 'desc')
            ->get()
            ->map(function (SubscriptionInvoice $invoice) {
                return [
                    'company' => $invoice->company->name,
                    'invoice_number' => $invoice->invoice_number,
                    'amount' => $invoice->amount,
                    'issue_date' => $invoice->issue_date->toDateString(),
                    'due_date' => $invoice->due_date->toDateString(),
                    'status' => $invoice->status,
                    'overdue' => $invoice->isOverdue() ? 'Yes' : 'No',
                ];
            });
    }
}
