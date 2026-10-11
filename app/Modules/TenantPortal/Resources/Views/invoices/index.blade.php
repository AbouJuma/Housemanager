@extends('tenant-portal::layout')

@section('title', __('My Invoices'))

@section('content')
    <h1 class="h4 mb-4">{{ __('My Invoices') }}</h1>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            @if ($invoices->isEmpty())
                <p class="text-secondary small mb-0">{{ __('No invoices yet.') }}</p>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>{{ __('Number') }}</th>
                                <th>{{ __('Property') }}</th>
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
                                    <td class="small text-secondary">{{ $invoice->lease->unit->building->property->name }}</td>
                                    <td class="small">{{ $invoice->due_date->format('M j, Y') }}</td>
                                    <td>{{ number_format($invoice->total_amount, 2) }}</td>
                                    <td>{{ number_format($invoice->balanceDue(), 2) }}</td>
                                    <td><span class="badge text-bg-secondary text-capitalize">{{ str_replace('_', ' ', $invoice->status) }}</span></td>
                                    <td class="text-end">
                                        <a href="{{ route('portal.invoices.show', $invoice) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
