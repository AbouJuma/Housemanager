@extends('layouts.app')

@section('title', __('Invoices'))

@section('page-heading')
    <h1 class="h4 mb-0">{{ __('Invoices') }}</h1>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0 d-lg-none">{{ __('Invoices') }}</h1>
            <p class="text-secondary mb-0">{{ $invoices->total() }} {{ Str::plural('invoice', $invoices->total()) }}</p>
        </div>
        @can('create', \App\Modules\Invoices\Models\Invoice::class)
            <a href="{{ route('invoices.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> {{ __('New Invoice') }}
            </a>
        @endcan
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-auto">
                    <div class="input-group" style="max-width: 320px;">
                        <span class="input-group-text bg-transparent"><i class="bi bi-search"></i></span>
                        <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search by number, tenant, or unit…">
                    </div>
                </div>
                <div class="col-auto">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">{{ __('All statuses') }}</option>
                        @foreach (['draft', 'sent', 'partially_paid', 'paid', 'overdue', 'cancelled'] as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ __(ucfirst(str_replace('_', ' ', $status))) }}</option>
                        @endforeach
                    </select>
                </div>
            </form>

            @if ($invoices->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-receipt display-4 text-secondary"></i>
                    <p class="mt-3 mb-1 fw-semibold">{{ __('No invoices yet') }}</p>
                    <p class="text-secondary">{{ __('Invoices you create will show up here.') }}</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>{{ __('Number') }}</th>
                                <th>{{ __('Customer / Unit') }}</th>
                                <th>{{ __('Due') }}</th>
                                <th>{{ __('Total') }}</th>
                                <th>{{ __('Balance') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th class="text-end">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($invoices as $invoice)
                                <tr>
                                    <td class="fw-semibold">{{ $invoice->invoice_number }}</td>
                                    <td>
                                        @if($invoice->lease)
                                            <div>{{ $invoice->lease->tenant->name }}</div>
                                            <div class="small text-secondary">{{ $invoice->lease->unit->unit_number }}</div>
                                        @elseif($invoice->sale)
                                            <div>{{ $invoice->sale->buyer->name }}</div>
                                            <div class="small text-secondary">{{ $invoice->sale->unit->unit_number }}</div>
                                        @else
                                            <div class="text-secondary">—</div>
                                        @endif
                                    </td>
                                    <td class="small">{{ $invoice->due_date->format('M j, Y') }}</td>
                                    <td>{{ number_format($invoice->total_amount, 2) }}</td>
                                    <td>{{ number_format($invoice->balanceDue(), 2) }}</td>
                                    <td>
                                        <span class="badge text-bg-{{ match($invoice->status) { 'paid' => 'success', 'overdue' => 'danger', 'cancelled' => 'secondary', 'partially_paid' => 'warning', default => 'info' } }} text-capitalize">{{ str_replace('_', ' ', $invoice->status) }}</span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                                        @can('update', $invoice)
                                            @if ($invoice->isEditable())
                                                <a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                                            @endif
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $invoices->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
