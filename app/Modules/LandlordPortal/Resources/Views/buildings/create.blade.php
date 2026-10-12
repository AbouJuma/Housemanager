@extends('landlord-portal::layout')

@section('title', __('Add Building'))

@section('content')
    <h1 class="h4 mb-1">{{ __('Add Building') }}</h1>
    <p class="text-secondary mb-4">{{ $property->name }}</p>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('landlord-portal.buildings.store', $property) }}">
                @csrf
                @include('properties::buildings._form')

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">{{ __('Add Building') }}</button>
                    <a href="{{ route('landlord-portal.properties.show', $property) }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection
