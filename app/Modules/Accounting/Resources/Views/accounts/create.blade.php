@extends('layouts.app')

@section('title', __('New Account'))

@section('page-heading')
    <h1 class="h4 mb-0">{{ __('New Account') }}</h1>
@endsection

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('accounts.store') }}">
                @csrf
                @include('accounting::accounts._form')

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Create Account</button>
                    <a href="{{ route('accounts.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection
