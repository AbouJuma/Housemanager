@extends('layouts.app')

@section('title', 'Edit ' . $property->name)

@section('page-heading')
    <h1 class="h4 mb-0">Edit {{ $property->name }}</h1>
@endsection

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('properties.update', $property) }}">
                @csrf
                @method('PUT')
                @include('properties::properties._form')

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="{{ route('properties.show', $property) }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
