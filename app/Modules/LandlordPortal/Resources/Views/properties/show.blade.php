@extends('landlord-portal::layout')

@section('title', $property->name)

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 mb-0">{{ $property->name }}</h1>
        <a href="{{ route('landlord-portal.properties.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> All Properties
        </a>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h6 mb-3">Details</h2>
            <div class="row g-3 small">
                <div class="col-md-4">
                    <div class="text-secondary">Type</div>
                    <div class="text-capitalize">{{ str_replace('_', ' ', $property->type) }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-secondary">Status</div>
                    <div class="text-capitalize">{{ $property->status }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-secondary">Address</div>
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
                                    <th>Unit</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Rent</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($building->units as $unit)
                                    <tr>
                                        <td class="fw-semibold">{{ $unit->unit_number }}</td>
                                        <td class="text-capitalize">{{ str_replace('_', ' ', $unit->type) }}</td>
                                        <td><span class="badge text-bg-{{ $unit->isVacant() ? 'warning' : 'success' }} text-capitalize">{{ $unit->status }}</span></td>
                                        <td>{{ number_format($unit->rent_amount, 2) }}</td>
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
