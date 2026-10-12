@extends('landlord-portal::layout')

@section('title', $property->name)

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 mb-0">{{ $property->name }}</h1>
        <div class="d-flex gap-2">
            @if ($canUpdate)
                <a href="{{ route('landlord-portal.properties.edit', $property) }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-pencil me-1"></i> {{ __('Edit') }}
                </a>
            @endif
            <a href="{{ route('landlord-portal.properties.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> {{ __('All Properties') }}
            </a>
        </div>
    </div>

    @php
        $allUnits = $property->buildings->flatMap->units;
    @endphp
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100"><div class="card-body">
                <div class="text-secondary small">{{ __('Units') }}</div>
                <div class="h4 mb-0">{{ $allUnits->count() }}</div>
            </div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100"><div class="card-body">
                <div class="text-secondary small">{{ __('Rented') }}</div>
                <div class="h4 mb-0 text-success">{{ $allUnits->where('status', 'occupied')->count() }}</div>
            </div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100"><div class="card-body">
                <div class="text-secondary small">{{ __('Free') }}</div>
                <div class="h4 mb-0 text-warning">{{ $allUnits->where('status', 'vacant')->count() }}</div>
            </div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100"><div class="card-body">
                <div class="text-secondary small">{{ __('Monthly rent (rented units)') }}</div>
                <div class="h5 mb-0">{{ number_format($allUnits->where('status', 'occupied')->sum('rent_amount'), 2) }}</div>
            </div></div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h6 mb-3">{{ __('Details') }}</h2>
            <div class="row g-3 small">
                <div class="col-md-4">
                    <div class="text-secondary">{{ __('Type') }}</div>
                    <div class="text-capitalize">{{ str_replace('_', ' ', $property->type) }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-secondary">{{ __('Status') }}</div>
                    <div class="text-capitalize">{{ $property->status }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-secondary">{{ __('Address') }}</div>
                    <div>{{ collect([$property->address_line1, $property->city, $property->country_code])->filter()->implode(', ') ?: '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h2 class="h6 mb-3">Buildings &amp; Units</h2>
            @if ($property->buildings->isEmpty())
                <p class="text-secondary small mb-0">No buildings on file.</p>
            @else
                @foreach ($property->buildings as $building)
                    <h3 class="h6 text-secondary mt-3">{{ $building->name }}</h3>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>{{ __('Unit') }}</th>
                                    <th>{{ __('Type') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Rent') }}</th>
                                    <th>{{ __('Tenant') }}</th>
                                    <th>Buyer / Payment</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($building->units as $unit)
                                    @php($tenant = $unit->currentTenant())
                                    @php($sale = $unit->currentSale())
                                    <tr>
                                        <td class="fw-semibold">{{ $unit->unit_number }}</td>
                                        <td class="text-capitalize">{{ str_replace('_', ' ', $unit->type) }}</td>
                                        <td><span class="badge text-bg-{{ $unit->isVacant() ? 'warning' : ($unit->status === 'occupied' ? 'success' : 'secondary') }}">{{ $unit->status === 'occupied' ? __('Rented') : ($unit->isVacant() ? __('Free') : __(ucfirst(str_replace('_', ' ', $unit->status)))) }}</span></td>
                                        <td>{{ number_format($unit->rent_amount, 2) }}</td>
                                        <td>
                                            @if ($tenant)
                                                {{ $tenant->name }}
                                                @if ($tenant->phone)
                                                    <div class="text-secondary small">{{ $tenant->phone }}</div>
                                                @endif
                                            @else
                                                <span class="text-secondary">&mdash;</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($sale)
                                                <div>{{ $sale->buyer?->name ?? 'Unknown buyer' }}</div>
                                                <div class="text-secondary small">
                                                    Paid {{ number_format($sale->total_paid, 2) }} of {{ number_format($sale->total_price, 2) }}
                                                    @if ($sale->remaining_balance > 0)
                                                        <span class="text-warning">({{ number_format($sale->remaining_balance, 2) }} remaining{{ $sale->total_paid > 0 ? ' - partial payment' : '' }})</span>
                                                    @else
                                                        <span class="text-success">(paid in full)</span>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="text-secondary">&mdash;</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
@endsection
