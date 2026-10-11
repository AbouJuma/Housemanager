@extends('layouts.app')

@section('title', __('Check In Visitor'))

@section('page-heading')
    <h1 class="h4 mb-0">{{ __('Check In Visitor') }}</h1>
@endsection

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('visitors.store') }}">
                @csrf
                @include('visitor-management::_form')

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">{{ __('Check In') }}</button>
                    <a href="{{ route('visitors.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection
