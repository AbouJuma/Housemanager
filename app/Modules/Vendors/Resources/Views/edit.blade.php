@extends('layouts.app')

@section('title', 'Edit ' . $vendor->name)

@section('page-heading')
    <h1 class="h4 mb-0">Edit {{ $vendor->name }}</h1>
@endsection

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('vendors.update', $vendor) }}">
                @csrf
                @method('PUT')
                @include('vendors::_form')

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">{{ __('Save Changes') }}</button>
                    <a href="{{ route('vendors.show', $vendor) }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection
