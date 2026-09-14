@extends('layouts.app')

@section('title', 'Edit ' . $building->name)

@section('page-heading')
    <h1 class="h4 mb-0">Edit {{ $building->name }}</h1>
@endsection

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('buildings.update', $building) }}">
                @csrf
                @method('PUT')
                @include('properties::buildings._form')

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="{{ route('buildings.show', $building) }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
