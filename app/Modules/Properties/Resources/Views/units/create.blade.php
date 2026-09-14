@extends('layouts.app')

@section('title', 'New Unit')

@section('page-heading')
    <h1 class="h4 mb-0">New Unit</h1>
@endsection

@section('content')
    <p class="text-secondary mb-3">For {{ $building->name }}</p>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('units.store', $building) }}">
                @csrf
                @include('properties::units._form')

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Create Unit</button>
                    <a href="{{ route('units.create_multiple', $building) }}" class="btn btn-outline-primary">
                        <i class="bi bi-plus-lg"></i> Create Multiple
                    </a>
                    <a href="{{ route('buildings.show', $building) }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
