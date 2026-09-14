@extends('layouts.app')

@section('title', 'Edit Unit ' . $unit->unit_number)

@section('page-heading')
    <h1 class="h4 mb-0">Edit Unit {{ $unit->unit_number }}</h1>
@endsection

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('units.update', $unit) }}">
                @csrf
                @method('PUT')
                @include('properties::units._form')

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="{{ route('units.show', $unit) }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
