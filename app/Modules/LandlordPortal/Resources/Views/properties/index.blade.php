@extends('landlord-portal::layout')

@section('title', __('My Properties'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 mb-0">{{ __('My Properties') }}</h1>
        @if ($canCreate)
            <a href="{{ route('landlord-portal.properties.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> {{ __('Add Property') }}
            </a>
        @endif
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            @if ($properties->isEmpty())
                <p class="text-secondary small mb-0">{{ __('No properties on file.') }}</p>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Type') }}</th>
                                <th>{{ __('Location') }}</th>
                                <th>{{ __('Units') }}</th>
                                <th>{{ __('Rented') }}</th>
                                <th>{{ __('Free') }}</th>
                                <th class="text-end">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($properties as $property)
                                <tr>
                                    <td class="fw-semibold">{{ $property->name }}</td>
                                    <td>{{ __(ucfirst(str_replace('_', ' ', $property->type))) }}</td>
                                    <td class="small">{{ collect([$property->area, $property->city, $property->state])->filter()->implode(', ') ?: '—' }}</td>
                                    <td>{{ $property->units_count }}</td>
                                    <td><span class="badge text-bg-success">{{ $property->occupied_units_count }}</span></td>
                                    <td><span class="badge text-bg-warning">{{ $property->vacant_units_count }}</span></td>
                                    <td class="text-end">
                                        <a href="{{ route('landlord-portal.properties.show', $property) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('View') }}"><i class="bi bi-eye"></i></a>
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
