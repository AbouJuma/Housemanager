@extends('layouts.app')

@section('title', __('Billing'))

@section('page-heading')
    <h1 class="h4 mb-0">{{ __('Billing') }}</h1>
@endsection

@section('content')
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h6 mb-3">Current Plan</h2>

                    @if ($subscription)
                        <dl class="row mb-0">
                            <dt class="col-sm-4 text-secondary fw-normal">{{ __('Plan') }}</dt>
                            <dd class="col-sm-8">{{ $subscription->plan->name }}</dd>
                            <dt class="col-sm-4 text-secondary fw-normal">{{ __('Status') }}</dt>
                            <dd class="col-sm-8">
                                <span class="badge text-bg-{{ match ($subscription->status) {
                                    'active' => 'success',
                                    'trialing' => 'info',
                                    'past_due' => 'warning',
                                    default => 'secondary',
                                } }}">{{ ucfirst(str_replace('_', ' ', $subscription->status)) }}</span>
                            </dd>
                            <dt class="col-sm-4 text-secondary fw-normal">{{ __('Price') }}</dt>
                            <dd class="col-sm-8">{{ number_format($subscription->plan->price, 2) }} / {{ $subscription->plan->billing_cycle }}</dd>
                            <dt class="col-sm-4 text-secondary fw-normal">{{ __('Current Period') }}</dt>
                            <dd class="col-sm-8">{{ $subscription->current_period_start->format('M j, Y') }} &ndash; {{ $subscription->current_period_end->format('M j, Y') }}</dd>
                        </dl>
                    @else
                        <p class="text-secondary mb-0">No subscription on file yet.</p>
                    @endif
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-column flex-md-row">
                        <h2 class="h6 mb-0">Invoice History</h2>
                        <div class="d-flex gap-2 align-items-center">
                            <label for="billingInvoiceStatusFilter" class="small text-secondary mb-0">{{ __('Filter status') }}</label>
                            <select id="billingInvoiceStatusFilter" class="form-select form-select-sm" data-dt-filter="billingHistoryTable" data-dt-filter-column="4">
                                <option value="">{{ __('All statuses') }}</option>
                                <option value="unpaid">{{ __('Unpaid') }}</option>
                                <option value="paid">{{ __('Paid') }}</option>
                                <option value="void">{{ __('Void') }}</option>
                            </select>
                        </div>
                    </div>

                    @if ($invoices->isEmpty())
                        <p class="text-secondary small mb-0">{{ __('No invoices yet.') }}</p>
                    @else
                        <div class="table-responsive">
                            <table id="billingHistoryTable" class="table table-striped table-hover table-sm align-middle datatable">
                                <thead>
                                    <tr>
                                        <th>Invoice #</th>
                                        <th>{{ __('Amount') }}</th>
                                        <th>{{ __('Issue Date') }}</th>
                                        <th>{{ __('Due Date') }}</th>
                                        <th>{{ __('Status') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($invoices as $invoice)
                                        <tr>
                                            <td class="fw-semibold">{{ $invoice->invoice_number }}</td>
                                            <td>{{ number_format($invoice->amount, 2) }}</td>
                                            <td class="small">{{ $invoice->issue_date->format('M j, Y') }}</td>
                                            <td class="small">{{ $invoice->due_date->format('M j, Y') }}</td>
                                            <td>
                                                <span class="badge text-bg-{{ match ($invoice->status) {
                                                    'paid' => 'success',
                                                    'void' => 'secondary',
                                                    default => $invoice->isOverdue() ? 'danger' : 'warning',
                                                } }}">{{ ucfirst($invoice->status) }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
