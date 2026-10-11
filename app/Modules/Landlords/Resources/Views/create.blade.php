@extends('layouts.app')

@section('title', __('New Landlord'))

@section('page-heading')
    <h1 class="h4 mb-0">{{ __('New Landlord') }}</h1>
@endsection

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('landlords.store') }}">
                @csrf
                @include('landlords::_form')

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">{{ __('Create Landlord') }}</button>
                    <a href="{{ route('landlords.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection
