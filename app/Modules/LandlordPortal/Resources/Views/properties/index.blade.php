@extends('landlord-portal::layout')

@section('title', __('My Properties'))

@section('content')
    <h1 class="h4 mb-4">{{ __('My Properties') }}</h1>

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
                                <th>{{ __('Buildings') }}</th>
                                <th class="text-end">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($properties as $property)
                                <tr>
                                    <td class="fw-semibold">{{ $property->name }}</td>
                                    <td class="text-capitalize">{{ str_replace('_', ' ', $property->type) }}</td>
                                    <td>{{ $property->buildings_count }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('landlord-portal.properties.show', $property) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
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
