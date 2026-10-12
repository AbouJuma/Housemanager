@php
    $percent = $sale->completion_percentage;
    $unit = $sale->unit;
@endphp
<a href="{{ route('buyer-portal.purchases.show', $sale) }}" class="d-block text-reset text-decoration-none border rounded-3 p-3 mb-2">
    <div class="d-flex justify-content-between align-items-start gap-2">
        <div>
            <div class="fw-semibold">{{ $unit?->building?->property?->name ?? __('Property') }} · {{ __('Unit') }} {{ $unit?->unit_number }}</div>
            <div class="small text-secondary">{{ __('Bought :date', ['date' => $sale->sale_date?->format('d M Y')]) }}</div>
        </div>
        <span class="badge text-bg-{{ $sale->status === 'completed' ? 'success' : ($sale->status === 'cancelled' ? 'secondary' : 'primary') }}">{{ __(ucfirst($sale->status)) }}</span>
    </div>
    <div class="progress mt-3" style="height: 6px;">
        <div class="progress-bar bg-success" style="width: {{ $percent }}%"></div>
    </div>
    <div class="d-flex justify-content-between small text-secondary mt-1">
        <span>{{ __('Paid :paid of :total', ['paid' => number_format($sale->total_paid, 2), 'total' => number_format((float) $sale->total_price, 2)]) }}</span>
        <span>{{ $percent }}%</span>
    </div>
</a>
