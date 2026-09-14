@extends('layouts.app')

@section('title', 'Properties')

@section('page-heading')
    <h1 class="h4 mb-0">Properties</h1>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0 d-lg-none">Properties</h1>
            <p class="text-secondary mb-0">{{ $properties->total() }} {{ Str::plural('property', $properties->total()) }}</p>
        </div>
        @can('create', \App\Modules\Properties\Models\Property::class)
            <a href="{{ route('properties.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> New Property
            </a>
        @endcan
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-auto">
                    <div class="input-group" style="max-width: 320px;">
                        <span class="input-group-text bg-transparent"><i class="bi bi-search"></i></span>
                        <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search properties…">
                    </div>
                </div>
                <div class="col-auto">
                    <select name="type" class="form-select" onchange="this.form.submit()">
                        <option value="">All types</option>
                        @foreach (['residential', 'commercial', 'mixed_use', 'warehouse', 'retail', 'hotel', 'hostel', 'industrial', 'land', 'other'] as $type)
                            <option value="{{ $type }}" @selected(request('type') === $type)>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">All statuses</option>
                        @foreach (['active', 'inactive', 'under_maintenance', 'archived'] as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                        @endforeach
                    </select>
                </div>
            </form>

            @if ($properties->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-buildings display-4 text-secondary"></i>
                    <p class="mt-3 mb-1 fw-semibold">No properties yet</p>
                    <p class="text-secondary">Properties you add will show up here.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Property</th>
                                <th>Landlord</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Buildings</th>
                                <th>Units</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($properties as $property)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $property->name }}</div>
                                        <div class="text-secondary small">{{ $property->city }}{{ $property->city && $property->country_code ? ', ' : '' }}{{ $property->country_code }}</div>
                                    </td>
                                    <td>
                                        @if ($property->landlord)
                                            <a href="{{ route('landlords.show', $property->landlord) }}">{{ $property->landlord->name }}</a>
                                        @else
                                            <span class="text-secondary">&mdash;</span>
                                        @endif
                                    </td>
                                    <td class="text-capitalize">{{ str_replace('_', ' ', $property->type) }}</td>
                                    <td>
                                        <span class="badge text-bg-{{ match ($property->status) {
                                            'active' => 'success',
                                            'under_maintenance' => 'warning',
                                            'archived' => 'secondary',
                                            default => 'secondary',
                                        } }} text-capitalize">{{ str_replace('_', ' ', $property->status) }}</span>
                                    </td>
                                    <td>{{ $property->buildings_count }}</td>
                                    <td>{{ $property->units_count }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('properties.show', $property) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                                        @can('update', $property)
                                            <a href="{{ route('properties.edit', $property) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                                        @endcan
                                        @can('delete', $property)
                                            <form action="{{ route('properties.destroy', $property) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn btn-sm btn-outline-danger js-confirm-delete" data-name="{{ $property->name }}">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $properties->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
