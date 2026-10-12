@extends('buyer-portal::layout')

@section('title', __('Purchase'))

@section('content')
    @php
        $money = fn ($value) => number_format((float) $value, 2);
        $unit = $sale->unit;
        $badge = fn (string $status) => match ($status) { 'paid', 'completed' => 'success', 'overdue', 'cancelled' => 'danger', 'partially_paid' => 'warning', default => 'secondary' };
    @endphp

    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-0">{{ $unit?->building?->property?->name ?? __('Property') }} · {{ __('Unit') }} {{ $unit?->unit_number }}</h1>
            <div class="text-secondary small">{{ __('Bought :date', ['date' => $sale->sale_date?->format('d M Y')]) }}</div>
        </div>
        <a href="{{ route('buyer-portal.purchases.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> {{ __('All Purchases') }}</a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="text-secondary small">{{ __('Price') }}</div><div class="h5 mb-0">{{ $money($sale->total_price) }}</div>
        </div></div></div>
        <div class="col-6 col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="text-secondary small">{{ __('Paid') }}</div><div class="h5 mb-0 text-success">{{ $money($sale->total_paid) }}</div>
        </div></div></div>
        <div class="col-6 col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="text-secondary small">{{ __('Remaining') }}</div><div class="h5 mb-0 {{ $sale->remaining_balance > 0 ? 'text-danger' : '' }}">{{ $money($sale->remaining_balance) }}</div>
        </div></div></div>
        <div class="col-6 col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="text-secondary small">{{ __('Progress') }}</div><div class="h5 mb-0">{{ $sale->completion_percentage }}%</div>
        </div></div></div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 mb-3">{{ __('Instalment schedule') }}</h2>
                    @if ($sale->installments->isEmpty())
                        <p class="text-secondary small mb-0">{{ __('No instalments - paid in one go.') }}</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead><tr><th>#</th><th>{{ __('Due') }}</th><th class="text-end">{{ __('Amount') }}</th><th class="text-end">{{ __('Paid') }}</th><th>{{ __('Status') }}</th></tr></thead>
                                <tbody>
                                    @foreach ($sale->installments as $installment)
                                        <tr>
                                            <td>{{ $installment->installment_number }}</td>
                                            <td>{{ $installment->due_date?->format('d M Y') }}</td>
                                            <td class="text-end">{{ $money($installment->amount) }}</td>
                                            <td class="text-end">{{ $money($installment->paid_amount) }}</td>
                                            <td><span class="badge text-bg-{{ $badge($installment->status) }}">{{ __(ucfirst(str_replace('_', ' ', $installment->status))) }}</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 mb-3">{{ __('Invoices') }}</h2>
                    @forelse ($invoices as $invoice)
                        <a href="{{ route('buyer-portal.invoices.show', $invoice) }}" class="d-flex justify-content-between align-items-center border-bottom py-2 text-reset text-decoration-none small">
                            <span>
                                <span class="fw-semibold">{{ $invoice->invoice_number }}</span>
                                <span class="d-block text-secondary">{{ __('Due :date', ['date' => $invoice->due_date?->format('d M Y')]) }}</span>
                            </span>
                            <span class="text-end">
                                {{ $money($invoice->total_amount) }}
                                <span class="d-block"><span class="badge text-bg-{{ $badge($invoice->status) }}">{{ __(ucfirst(str_replace('_', ' ', $invoice->status))) }}</span></span>
                            </span>
                        </a>
                    @empty
                        <p class="text-secondary small mb-0">{{ __('No invoices yet.') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
