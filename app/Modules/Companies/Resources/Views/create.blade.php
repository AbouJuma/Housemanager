@extends('layouts.app')

@section('title', __('New Company'))

@section('page-heading')
    <h1 class="h4 mb-0">{{ __('New Company') }}</h1>
@endsection

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('companies.store') }}">
                @csrf
                @include('companies::_form')

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">{{ __('Create Company') }}</button>
                    <a href="{{ route('companies.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection
