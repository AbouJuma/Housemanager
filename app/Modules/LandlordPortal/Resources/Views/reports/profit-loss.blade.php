@extends('landlord-portal::layout')

@section('title', __('Profit & Loss'))

@section('content')
    @php
        $today = company_now();
        $presets = [
            __('This month') => [$today->copy()->startOfMonth()->toDateString(), $today->toDateString()],
            __('Last 3 months') => [$today->copy()->subMonthsNoOverflow(2)->startOfMonth()->toDateString(), $today->toDateString()],
            __('This year') => [$today->copy()->startOfYear()->toDateString(), $today->toDateString()],
            __('Last year') => [$today->copy()->subYear()->startOfYear()->toDateString(), $today->copy()->subYear()->endOfYear()->toDateString()],
        ];
        $money = fn ($value) => number_format((float) $value, 2);
    @endphp

    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">{{ __('Profit & Loss') }}</h1>
        <div class="text-secondary small">{{ \Illuminate\Support\Carbon::parse($report['from'])->format('d M Y') }} – {{ \Illuminate\Support\Carbon::parse($report['to'])->format('d M Y') }}</div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2 mb-3">
                @foreach ($presets as $label => [$from, $to])
                    <a href="{{ route('landlord-portal.reports.profit-loss', ['from' => $from, 'to' => $to]) }}"
                       class="btn btn-sm {{ $report['from'] === $from && $report['to'] === $to ? 'btn-primary' : 'btn-outline-primary' }}">{{ $label }}</a>
                @endforeach
            </div>
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-sm-4 col-md-3">
                    <label class="form-label small">{{ __('From') }}</label>
                    <input type="date" name="from" value="{{ $report['from'] }}" class="form-control form-control-sm">
                </div>
                <div class="col-sm-4 col-md-3">
                    <label class="form-label small">{{ __('To') }}</label>
                    <input type="date" name="to" value="{{ $report['to'] }}" class="form-control form-control-sm">
                </div>
                <div class="col-sm-4 col-md-2">
                    <button class="btn btn-sm btn-primary w-100">{{ __('Apply') }}</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100"><div class="card-body">
                <div class="text-secondary small">{{ __('Income received') }}</div>
                <div class="h4 mb-1 text-success">{{ $money($report['income']['total']) }}</div>
                <div class="small text-secondary">{{ __('Rent') }} {{ $money($report['income']['rent']) }} · {{ __('Sales') }} {{ $money($report['income']['sales']) }}</div>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100"><div class="card-body">
                <div class="text-secondary small">{{ __('Expenses') }}</div>
                <div class="h4 mb-1 text-danger">{{ $money($report['expenses']['total']) }}</div>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100"><div class="card-body">
                <div class="text-secondary small">{{ $report['net'] >= 0 ? __('Net profit') : __('Net loss') }}</div>
                <div class="h4 mb-1 {{ $report['net'] >= 0 ? 'text-success' : 'text-danger' }}">{{ $money($report['net']) }}</div>
                @if ($report['margin'] !== null)
                    <div class="small text-secondary">{{ __('Margin') }} {{ $report['margin'] }}%</div>
                @endif
            </div></div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 mb-3">{{ __('By month') }}</h2>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th>{{ __('Month') }}</th><th class="text-end">{{ __('Income') }}</th><th class="text-end">{{ __('Expenses') }}</th><th class="text-end">{{ __('Net') }}</th></tr></thead>
                            <tbody>
                                @foreach ($report['monthly'] as $row)
                                    <tr>
                                        <td>{{ \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $row['month'].'-01')->translatedFormat('M Y') }}</td>
                                        <td class="text-end">{{ $money($row['income']) }}</td>
                                        <td class="text-end">{{ $money($row['expenses']) }}</td>
                                        <td class="text-end fw-semibold {{ $row['net'] >= 0 ? 'text-success' : 'text-danger' }}">{{ $money($row['net']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h6 mb-3">{{ __('By property') }}</h2>
                    @forelse ($report['by_property'] as $row)
                        <div class="d-flex justify-content-between border-bottom py-2 small">
                            <span class="fw-semibold">{{ $row['name'] }}</span>
                            <span class="{{ $row['net'] >= 0 ? 'text-success' : 'text-danger' }}">{{ $money($row['net']) }}</span>
                        </div>
                    @empty
                        <p class="text-secondary small mb-0">{{ __('No properties on file.') }}</p>
                    @endforelse
                </div>
            </div>
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h6 mb-3">{{ __('Expenses by category') }}</h2>
                    @forelse ($report['expenses']['by_category'] as $row)
                        <div class="d-flex justify-content-between border-bottom py-2 small">
                            <span>{{ $row['category'] }}</span>
                            <span>{{ $money($row['amount']) }}</span>
                        </div>
                    @empty
                        <p class="text-secondary small mb-0">{{ __('No expenses in this period.') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
