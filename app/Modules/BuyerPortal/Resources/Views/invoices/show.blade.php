@extends('buyer-portal::layout')

@section('title', $invoice->invoice_number)

@section('content')
    @php
        $money = fn ($value) => number_format((float) $value, 2);
        $badge = match ($invoice->status) { 'paid' => 'success', 'overdue' => 'danger', 'partially_paid' => 'warning', default => 'secondary' };
    @endphp

    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-0">{{ $invoice->invoice_number }} <span class="badge text-bg-{{ $badge }} align-middle fs-6">{{ __(ucfirst(str_replace('_', ' ', $invoice->status))) }}</span></h1>
            <div class="text-secondary small">{{ $invoice->sale?->unit?->building?->property?->name }} · {{ __('Unit') }} {{ $invoice->sale?->unit?->unit_number }}</div>
        </div>
        <a href="{{ route('buyer-portal.invoices.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> {{ __('All invoices') }}</a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="text-secondary small">{{ __('Issued') }}</div><div class="fw-semibold">{{ $invoice->issue_date?->format('d M Y') }}</div>
        </div></div></div>
        <div class="col-6 col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="text-secondary small">{{ __('Due') }}</div><div class="fw-semibold">{{ $invoice->due_date?->format('d M Y') }}</div>
        </div></div></div>
        <div class="col-6 col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="text-secondary small">{{ __('Total') }}</div><div class="fw-semibold">{{ $money($invoice->total_amount) }}</div>
        </div></div></div>
        <div class="col-6 col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="text-secondary small">{{ __('Balance') }}</div><div class="fw-semibold {{ $invoice->balanceDue() > 0 ? 'text-danger' : 'text-success' }}">{{ $money($invoice->balanceDue()) }}</div>
        </div></div></div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100"><div class="card-body">
                <h2 class="h6 mb-3">{{ __('Items') }}</h2>
                <table class="table table-sm mb-0">
                    <tbody>
                        @forelse ($invoice->items as $item)
                            <tr><td>{{ $item->description }}</td><td class="text-end">{{ $money($item->amount) }}</td></tr>
                        @empty
                            <tr><td class="text-secondary">{{ __('Instalment payment') }}</td><td class="text-end">{{ $money($invoice->total_amount) }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div></div>
        </div>
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100"><div class="card-body">
                <h2 class="h6 mb-3">{{ __('Payments') }}</h2>
                @forelse ($invoice->payments as $payment)
                    <div class="d-flex justify-content-between border-bottom py-2 small">
                        <span>{{ $payment->paid_on?->format('d M Y') }} <span class="text-secondary">· {{ __(ucfirst(str_replace('_', ' ', (string) $payment->method))) }}</span></span>
                        <span class="fw-semibold text-success">{{ $money($payment->amount) }}</span>
                    </div>
                @empty
                    <p class="text-secondary small mb-0">{{ __('No payments yet.') }}</p>
                @endforelse
            </div></div>
        </div>
    </div>
@endsection
