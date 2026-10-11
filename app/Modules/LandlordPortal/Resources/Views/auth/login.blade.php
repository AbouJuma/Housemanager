@extends('layouts.guest')

@section('title', 'Landlord Portal Login · '.config('app.name'))
@section('auth-image', __('apartments'))
@section('auth-headline', __('Your portfolio, always in view.'))
@section('auth-sub', __('Occupancy, rent collected, expenses and maintenance across all your properties - updated as it happens.'))
@section('auth-eyebrow', __('Landlord portal'))
@section('auth-title', __('Welcome back'))
@section('auth-subtitle', __('Sign in to view your properties and financials.'))

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('landlord-portal.login') }}">
        @csrf
        <div class="mb-3">
            <label for="email" class="form-label">{{ __('Email, username or phone') }}</label>
            <input id="email" type="text" name="email" autocapitalize="none" spellcheck="false" value="{{ old('email') }}" class="form-control form-control-lg" required autofocus autocomplete="username">
        </div>
        <div class="mb-3">
            <label class="form-label" for="password">{{ __('Password') }}</label>
            <input type="password" id="password" name="password" class="form-control form-control-lg" required autocomplete="current-password">
        </div>
        <div class="mb-4 form-check">
            <input type="checkbox" name="remember" value="1" id="rememberMe" class="form-check-input">
            <label class="form-check-label" for="rememberMe">{{ __('Remember me') }}</label>
        </div>
        <button type="submit" class="btn btn-primary btn-lg w-100">{{ __('Sign In') }}</button>
    </form>

    <p class="text-secondary small mt-4 mb-0">
        {{ __('Are you a tenant?') }} <a href="{{ route('portal.login') }}" class="fw-semibold">{{ __('Use the tenant portal') }}</a>
    </p>
@endsection
