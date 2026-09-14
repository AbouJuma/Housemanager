@extends('layouts.app')

@section('title', 'New Property')

@section('page-heading')
    <h1 class="h4 mb-0">New Property</h1>
@endsection

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('properties.store') }}">
                @csrf
                @include('properties::properties._form')

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Create Property</button>
                    <a href="{{ route('properties.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
