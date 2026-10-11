@extends('layouts.app')

@section('title', __('Billing Status'))

@section('page-heading')
    <h1 class="h4 mb-0">{{ __('Billing Status') }}</h1>
@endsection

@section('content')
    <div class="mb-3 d-flex flex-column flex-md-row justify-content-between gap-2 align-items-start align-items-md-center">
        <div>
            <h1 class="h4 mb-0 d-lg-none">{{ __('Billing Status') }}</h1>
            <p class="text-secondary mb-0">{{ $rows->count() }} subscription invoices.</p>
        </div>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <label for="billingStatusFilter" class="small text-secondary mb-0">{{ __('Filter status') }}</label>
            <select id="billingStatusFilter" class="form-select form-select-sm" data-dt-filter="billingStatusTable" data-dt-filter-column="5">
                <option value="">{{ __('All statuses') }}</option>
                <option value="unpaid">{{ __('Unpaid') }}</option>
                <option value="paid">{{ __('Paid') }}</option>
                <option value="void">{{ __('Void') }}</option>
            </select>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            @if ($rows->isEmpty())
                <p class="text-secondary small mb-0">No billing records are available.</p>
            @else
                <div class="table-responsive">
                    <table id="billingStatusTable" class="table table-striped table-hover table-sm align-middle datatable">
                        <thead>
                            <tr>
                                <th>{{ __('Company') }}</th>
                                <th>Invoice #</th>
                                <th class="text-end">{{ __('Amount') }}</th>
                                <th>{{ __('Issue Date') }}</th>
                                <th>{{ __('Due Date') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>Overdue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr>
                                    <td>{{ $row['company'] }}</td>
                                    <td class="fw-semibold">{{ $row['invoice_number'] }}</td>
                                    <td class="text-end">{{ number_format($row['amount'], 2) }}</td>
                                    <td class="small">{{ $row['issue_date'] }}</td>
                                    <td class="small">{{ $row['due_date'] }}</td>
                                    <td><span class="badge text-bg-{{ $row['status'] === 'paid' ? 'success' : ($row['status'] === 'void' ? 'secondary' : 'warning') }} text-capitalize">{{ ucfirst($row['status']) }}</span></td>
                                    <td class="text-end">{{ $row['overdue'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
