@extends('layouts.app')

@section('title', 'Subscription Status')

@section('page-heading')
    <h1 class="h4 mb-0">Subscription Status</h1>
@endsection

@section('content')
    <div class="mb-3 d-flex flex-column flex-md-row justify-content-between gap-2 align-items-start align-items-md-center">
        <div>
            <h1 class="h4 mb-0 d-lg-none">Subscription Status</h1>
            <p class="text-secondary mb-0">{{ $rows->count() }} subscriptions tracked.</p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <label for="subscriptionStatusFilter" class="small text-secondary mb-0">Filter status</label>
            <select id="subscriptionStatusFilter" class="form-select form-select-sm" data-dt-filter="subscriptionStatusTable" data-dt-filter-column="2">
                <option value="">All statuses</option>
                <option value="active">Active</option>
                <option value="trialing">Trialing</option>
                <option value="past_due">Past Due</option>
                <option value="canceled">Canceled</option>
            </select>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            @if ($rows->isEmpty())
                <p class="text-secondary small mb-0">No subscription data is available.</p>
            @else
                <div class="table-responsive">
                    <table id="subscriptionStatusTable" class="table table-striped table-hover table-sm align-middle datatable">
                        <thead>
                            <tr>
                                <th>Company</th>
                                <th>Plan</th>
                                <th>Status</th>
                                <th>Current Period</th>
                                <th class="text-end">Unpaid Invoices</th>
                                <th class="text-end">Overdue Invoices</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr>
                                    <td class="fw-semibold">{{ $row['company'] }}</td>
                                    <td>{{ $row['plan'] }}</td>
                                    <td><span class="badge text-bg-{{ $row['status'] === 'active' ? 'success' : ($row['status'] === 'trialing' ? 'info' : ($row['status'] === 'past_due' ? 'warning' : 'secondary')) }} text-capitalize">{{ str_replace('_', ' ', $row['status']) }}</span></td>
                                    <td>{{ $row['current_period'] }}</td>
                                    <td class="text-end">{{ $row['unpaid_invoices'] }}</td>
                                    <td class="text-end">{{ $row['overdue_invoices'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
