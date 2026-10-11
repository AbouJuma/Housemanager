@extends('layouts.app')

@section('title', 'Unit ' . $unit->unit_number)

@section('page-heading')
    <h1 class="h4 mb-0">Unit {{ $unit->unit_number }}</h1>
@endsection

@section('content')
    <x-breadcrumb :items="collect([
        ['label' => 'Properties', 'url' => route('properties.index')],
        ['label' => $unit->building->property->name, 'url' => route('properties.show', $unit->building->property)],
        ['label' => $unit->building->name, 'url' => route('buildings.show', $unit->building)],
        ['label' => 'Unit ' . $unit->unit_number],
    ])" />

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0 d-lg-none">Unit {{ $unit->unit_number }}</h1>
            <p class="text-secondary mb-0">
                <a href="{{ route('buildings.show', $unit->building) }}">{{ $unit->building->name }}</a>
                &middot;
                <a href="{{ route('properties.show', $unit->building->property) }}">{{ $unit->building->property->name }}</a>
            </p>
        </div>
        <div class="d-flex gap-2">
            @can('update', $unit)
                <a href="{{ route('units.edit', $unit) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-pencil me-1"></i> {{ __('Edit') }}
                </a>
            @endcan
            @can('delete', $unit)
                <form action="{{ route('units.destroy', $unit) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="button" class="btn btn-outline-danger btn-sm js-confirm-delete" data-name="Unit {{ $unit->unit_number }}">
                        <i class="bi bi-trash me-1"></i> {{ __('Delete') }}
                    </button>
                </form>
            @endcan
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="row g-3 small">
                <div class="col-md-3">
                    <div class="text-secondary">{{ __('Type') }}</div>
                    <div class="text-capitalize">{{ str_replace('_', ' ', $unit->type) }}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-secondary">{{ __('Status') }}</div>
                    <div class="text-capitalize">{{ str_replace('_', ' ', $unit->status) }}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-secondary">Bedrooms / Bathrooms</div>
                    <div>{{ $unit->bedrooms ?? '—' }} / {{ $unit->bathrooms ?? '—' }}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-secondary">{{ __('Area') }}</div>
                    <div>{{ $unit->area ? number_format($unit->area, 2).' '.strtoupper($unit->area_unit) : '—' }}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-secondary">{{ __('Rent Amount') }}</div>
                    <div>{{ $unit->rent_amount ? number_format($unit->rent_amount, 2) : '—' }}</div>
                </div>
            </div>
            @if ($unit->description)
                <p class="mt-3 mb-0">{{ $unit->description }}</p>
            @endif
        </div>
    </div>

    <div class="card border-0 shadow-sm mt-4">
        <div class="card-body">
            <h2 class="h6 mb-3">{{ __('Lease') }}</h2>
            @if ($currentLease)
                <p class="mb-1">
                    <a href="{{ route('tenants.show', $currentLease->tenant) }}">{{ $currentLease->tenant->name }}</a>
                    &middot; {{ $currentLease->start_date->format('M j, Y') }} &ndash; {{ $currentLease->end_date->format('M j, Y') }}
                </p>
                <a href="{{ route('leases.show', $currentLease) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-eye me-1"></i> {{ __('View Lease') }}
                </a>
            @else
                <p class="text-secondary small mb-3">This unit is vacant - no active lease.</p>
                @can('create', \App\Modules\Leases\Models\Lease::class)
                    <a href="{{ route('leases.create', ['unit_id' => $unit->uuid]) }}" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus-lg me-1"></i> Start Lease
                    </a>
                @endcan
            @endif
        </div>
    </div>
@endsection
