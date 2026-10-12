@extends('landlord-portal::layout')

@section('title', __('Invoices'))

@section('content')
    @php
        $filters = ['pending' => __('Pending'), 'overdue' => __('Overdue'), 'partially_paid' => __('Partially paid'), 'paid' => __('Paid'), 'all' => __('All')];
        $badge = fn (string $s) => match ($s) { 'paid' => 'success', 'overdue' => 'danger', 'partially_paid' => 'warning', default => 'secondary' };
    @endphp

    <h1 class="h4 mb-4">{{ __('Invoices') }}</h1>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2 mb-3">
                @foreach ($filters as $value => $label)
                    <a href="{{ route('landlord-portal.invoices.index', array_filter(['status' => $value, 'period' => $period, 'from' => $from, 'to' => $to])) }}"
                       class="btn btn-sm rounded-pill {{ $status === $value ? 'btn-primary' : 'btn-outline-primary' }}">{{ $label }}</a>
                @endforeach
            </div>
            <div class="small text-secondary mb-2">{{ __('Due date') }}</div>
            @include('partials.period-filter', ['route' => 'landlord-portal.invoices.index', 'period' => $period, 'from' => $from, 'to' => $to, 'keep' => ['status' => $status]])
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="text-secondary small">{{ __('Invoices') }}</div><div class="h5 mb-0">{{ $summary['count'] }}</div>
        </div></div></div>
        <div class="col-6 col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="text-secondary small">{{ __('Still owed') }}</div><div class="h5 mb-0 {{ $summary['balance'] > 0 ? 'text-danger' : '' }}">{{ number_format($summary['balance'], 2) }}</div>
        </div></div></div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            @if ($rows->isEmpty())
                <p class="text-secondary small mb-0">{{ __('No invoices match.') }}</p>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>{{ __('Invoice') }}</th>
                                <th>{{ __('Who') }}</th>
                                <th>{{ __('Property / Unit') }}</th>
                                <th>{{ __('Due') }}</th>
                                <th class="text-end">{{ __('Amount') }}</th>
                                <th class="text-end">{{ __('Balance') }}</th>
                                <th>{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr>
                                    <td class="fw-semibold">{{ $row['invoice_number'] }}<div class="small text-secondary">{{ $row['kind'] === 'rent' ? __('Rent') : __('Sale') }}</div></td>
                                    <td>{{ $row['person']['name'] ?? '—' }}<div class="small text-secondary">{{ $row['person']['phone'] ?? '' }}</div></td>
                                    <td>{{ $row['property']['name'] ?? '—' }}<div class="small text-secondary">{{ __('Unit') }} {{ $row['unit_number'] }}</div></td>
                                    <td class="{{ $row['is_overdue'] ? 'text-danger fw-semibold' : '' }}">{{ $row['due_date'] ? \Illuminate\Support\Carbon::parse($row['due_date'])->format('d M Y') : '—' }}</td>
                                    <td class="text-end">{{ number_format($row['total_amount'], 2) }}</td>
                                    <td class="text-end fw-semibold">{{ number_format($row['balance_due'], 2) }}</td>
                                    <td><span class="badge text-bg-{{ $badge($row['status']) }}">{{ __(ucfirst(str_replace('_', ' ', $row['status']))) }}</span></td>
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
