@extends('layouts.app')

@section('title', $building->name)

@section('page-heading')
    <h1 class="h4 mb-0">{{ $building->name }}</h1>
@endsection

@section('content')
    <x-breadcrumb :items="collect([
        ['label' => 'Properties', 'url' => route('properties.index')],
        ['label' => $building->property->name, 'url' => route('properties.show', $building->property)],
        ['label' => $building->name],
    ])" />

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0 d-lg-none">{{ $building->name }}</h1>
            <p class="text-secondary mb-0">
                <a href="{{ route('properties.show', $building->property) }}">{{ $building->property->name }}</a>
            </p>
        </div>
        <div class="d-flex gap-2">
            @can('update', $building)
                <a href="{{ route('buildings.edit', $building) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-pencil me-1"></i> Edit
                </a>
            @endcan
            @can('delete', $building)
                <form action="{{ route('buildings.destroy', $building) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="button" class="btn btn-outline-danger btn-sm js-confirm-delete" data-name="{{ $building->name }}">
                        <i class="bi bi-trash me-1"></i> Delete
                    </button>
                </form>
            @endcan
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3 small">
                <div class="col-md-4">
                    <div class="text-secondary">Floors</div>
                    <div>{{ $building->floor_count ?? '—' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-secondary">Status</div>
                    <div class="text-capitalize">{{ str_replace('_', ' ', $building->status) }}</div>
                </div>
            </div>
            @if ($building->description)
                <p class="mt-3 mb-0">{{ $building->description }}</p>
            @endif
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h6 mb-0">Units</h2>
                @can('create', [\App\Modules\Properties\Models\Unit::class, $building])
                    <a href="{{ route('units.create', $building) }}" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus-lg me-1"></i> Add Unit
                    </a>
                @endcan
            </div>

            @if ($units->isEmpty())
                <div class="text-center py-4">
                    <i class="bi bi-door-closed display-6 text-secondary"></i>
                    <p class="mt-2 mb-0 text-secondary">No units yet.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Unit</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Rent</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($units as $unit)
                                <tr>
                                    <td class="fw-semibold">{{ $unit->unit_number }}</td>
                                    <td class="text-capitalize">{{ str_replace('_', ' ', $unit->type) }}</td>
                                    <td>
                                        <span class="badge text-bg-{{ match ($unit->status) {
                                            'vacant' => 'success',
                                            'occupied' => 'primary',
                                            'reserved' => 'info',
                                            default => 'secondary',
                                        } }} text-capitalize">{{ str_replace('_', ' ', $unit->status) }}</span>
                                    </td>
                                    <td>{{ $unit->rent_amount ? number_format($unit->rent_amount, 2) : '—' }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('units.show', $unit) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
