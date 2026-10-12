@extends('landlord-portal::layout')

@section('title', __('Units'))

@section('content')
    @php
        $summary = $report['summary'];
        $filters = ['' => __('All'), 'occupied' => __('Rented'), 'vacant' => __('Free'), 'reserved' => __('Reserved'), 'under_maintenance' => __('Under maintenance')];
    @endphp

    <h1 class="h4 mb-4">{{ __('Units') }}</h1>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100"><div class="card-body">
                <div class="text-secondary small">{{ __('Total units') }}</div>
                <div class="h4 mb-0">{{ $summary['total'] }}</div>
            </div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100"><div class="card-body">
                <div class="text-secondary small">{{ __('Rented') }}</div>
                <div class="h4 mb-0 text-success">{{ $summary['occupied'] }}</div>
                <div class="small text-secondary">{{ number_format($summary['monthly_rent_occupied'], 2) }} / {{ __('month') }}</div>
            </div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100"><div class="card-body">
                <div class="text-secondary small">{{ __('Free') }}</div>
                <div class="h4 mb-0 text-warning">{{ $summary['vacant'] }}</div>
                <div class="small text-secondary">{{ number_format($summary['monthly_rent_vacant'], 2) }} / {{ __('month') }}</div>
            </div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100"><div class="card-body">
                <div class="text-secondary small">{{ __('Occupancy') }}</div>
                <div class="h4 mb-0">{{ $summary['occupancy_rate'] }}%</div>
            </div></div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2 mb-3">
                @foreach ($filters as $value => $label)
                    <a href="{{ route('landlord-portal.reports.units', array_filter(['status' => $value])) }}"
                       class="btn btn-sm rounded-pill {{ (string) $status === (string) $value ? 'btn-primary' : 'btn-outline-primary' }}">{{ $label }}</a>
                @endforeach
            </div>

            @if (empty($report['units']))
                <p class="text-secondary small mb-0">{{ __('No units to show.') }}</p>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>{{ __('Unit') }}</th>
                                <th>{{ __('Property') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th class="text-end">{{ __('Rent') }}</th>
                                <th>{{ __('Tenant') }}</th>
                                <th>{{ __('Lease ends') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($report['units'] as $unit)
                                <tr>
                                    <td class="fw-semibold">{{ $unit['unit_number'] }}</td>
                                    <td>{{ $unit['property']['name'] ?? '—' }}<div class="text-secondary small">{{ $unit['building'] }}</div></td>
                                    <td>
                                        <span class="badge text-bg-{{ $unit['status'] === 'occupied' ? 'success' : ($unit['status'] === 'vacant' ? 'warning' : 'secondary') }}">
                                            {{ $filters[$unit['status']] ?? __(ucfirst(str_replace('_', ' ', $unit['status']))) }}
                                        </span>
                                    </td>
                                    <td class="text-end">{{ number_format($unit['rent_amount'], 2) }}</td>
                                    <td>
                                        @if ($unit['tenant'])
                                            {{ $unit['tenant']['name'] }}
                                            <div class="text-secondary small">{{ $unit['tenant']['phone'] }}</div>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>{{ $unit['lease_end'] ? \Illuminate\Support\Carbon::parse($unit['lease_end'])->format('d M Y') : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
