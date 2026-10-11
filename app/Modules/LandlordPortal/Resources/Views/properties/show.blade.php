@extends('landlord-portal::layout')

@section('title', $property->name)

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 mb-0">{{ $property->name }}</h1>
        <a href="{{ route('landlord-portal.properties.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> {{ __('All Properties') }}
        </a>
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
                                        <td><span class="badge text-bg-{{ $unit->isVacant() ? 'warning' : 'success' }} text-capitalize">{{ $unit->status }}</span></td>
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
