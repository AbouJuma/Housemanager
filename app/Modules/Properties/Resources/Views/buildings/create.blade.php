@extends('layouts.app')

@section('title', __('New Building'))

@section('page-heading')
    <h1 class="h4 mb-0">{{ __('New Building') }}</h1>
@endsection

@section('content')
    <p class="text-secondary mb-3">For {{ $property->name }}</p>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('buildings.store', $property) }}">
                @csrf
                @include('properties::buildings._form')

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Create Building</button>
                    <a href="{{ route('properties.show', $property) }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection
