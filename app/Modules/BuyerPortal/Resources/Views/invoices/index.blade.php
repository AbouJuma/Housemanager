@extends('buyer-portal::layout')

@section('title', __('Invoices'))

@section('content')
    @php
        $filters = ['' => __('All'), 'sent' => __('Sent'), 'partially_paid' => __('Partially paid'), 'overdue' => __('Overdue'), 'paid' => __('Paid')];
        $badge = fn (string $s) => match ($s) { 'paid' => 'success', 'overdue' => 'danger', 'partially_paid' => 'warning', default => 'secondary' };
    @endphp

    <h1 class="h4 mb-4">{{ __('Invoices') }}</h1>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2 mb-3">
                @foreach ($filters as $value => $label)
                    <a href="{{ route('buyer-portal.invoices.index', array_filter(['status' => $value])) }}"
                       class="btn btn-sm rounded-pill {{ (string) $status === (string) $value ? 'btn-primary' : 'btn-outline-primary' }}">{{ $label }}</a>
                @endforeach
            </div>

            @if ($invoices->isEmpty())
                <p class="text-secondary small mb-0">{{ __('No invoices yet.') }}</p>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>{{ __('Invoice') }}</th>
                                <th>{{ __('Property / Unit') }}</th>
                                <th>{{ __('Due') }}</th>
                                <th class="text-end">{{ __('Amount') }}</th>
                                <th class="text-end">{{ __('Balance') }}</th>
                                <th>{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($invoices as $invoice)
                                <tr>
                                    <td><a href="{{ route('buyer-portal.invoices.show', $invoice) }}" class="fw-semibold">{{ $invoice->invoice_number }}</a></td>
                                    <td>{{ $invoice->sale?->unit?->building?->property?->name }} <span class="text-secondary small">· {{ $invoice->sale?->unit?->unit_number }}</span></td>
                                    <td>{{ $invoice->due_date?->format('d M Y') }}</td>
                                    <td class="text-end">{{ number_format((float) $invoice->total_amount, 2) }}</td>
                                    <td class="text-end">{{ number_format($invoice->balanceDue(), 2) }}</td>
                                    <td><span class="badge text-bg-{{ $badge($invoice->status) }}">{{ __(ucfirst(str_replace('_', ' ', $invoice->status))) }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">{{ $invoices->links() }}</div>
            @endif
        </div>
    </div>
@endsection
