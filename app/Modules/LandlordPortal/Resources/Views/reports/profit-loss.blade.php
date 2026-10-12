@extends('landlord-portal::layout')

@section('title', __('Profit & Loss'))

@section('content')
    @php
        $money = fn ($value) => number_format((float) $value, 2);
        $groupTitle = ['day' => __('By day'), 'week' => __('By week'), 'month' => __('By month')][$report['group']];
    @endphp

    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">{{ __('Profit & Loss') }}</h1>
        <div class="text-secondary small">{{ \Illuminate\Support\Carbon::parse($report['from'])->format('d M Y') }} – {{ \Illuminate\Support\Carbon::parse($report['to'])->format('d M Y') }}</div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            @include('partials.period-filter', ['route' => 'landlord-portal.reports.profit-loss', 'period' => $period, 'from' => $report['from'], 'to' => $report['to'], 'group' => $report['group']])
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
                    <h2 class="h6 mb-3">{{ $groupTitle }}</h2>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th>{{ __('Period') }}</th><th class="text-end">{{ __('Income') }}</th><th class="text-end">{{ __('Expenses') }}</th><th class="text-end">{{ __('Net') }}</th></tr></thead>
                            <tbody>
                                @foreach ($report['breakdown'] as $row)
                                    <tr>
                                        <td>{{ $row['label'] }}</td>
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
