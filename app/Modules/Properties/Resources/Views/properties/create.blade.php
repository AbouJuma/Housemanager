@extends('layouts.app')

@section('title', __('New Property'))

@section('page-heading')
    <h1 class="h4 mb-0">{{ __('New Property') }}</h1>
@endsection

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('properties.store') }}">
                @csrf
                @include('properties::properties._form')

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">{{ __('Create Property') }}</button>
                    <a href="{{ route('properties.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection
