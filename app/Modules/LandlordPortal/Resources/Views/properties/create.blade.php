@extends('landlord-portal::layout')

@section('title', __('Add Property'))

@section('content')
    <h1 class="h4 mb-4">{{ __('Add Property') }}</h1>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('landlord-portal.properties.store') }}">
                @csrf

                @include('properties::properties._form', ['forLandlord' => true])

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">{{ __('Add Property') }}</button>
                    <a href="{{ route('landlord-portal.properties.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection
