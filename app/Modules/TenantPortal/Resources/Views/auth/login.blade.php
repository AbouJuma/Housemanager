@extends('layouts.guest')

@section('title', 'Tenant Portal Login · '.config('app.name'))
@section('auth-image', __('interior'))
@section('auth-headline', __('Your home, your lease, your payments.'))
@section('auth-sub', __('See what you owe, download invoices and receipts, and report maintenance issues - any time.'))
@section('auth-eyebrow', __('Tenant portal'))
@section('auth-title', __('Welcome home'))
@section('auth-subtitle', __('Sign in to view your lease and invoices.'))

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

    <form method="POST" action="{{ route('portal.login') }}">
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
        {{ __('Are you a landlord?') }} <a href="{{ route('landlord-portal.login') }}" class="fw-semibold">{{ __('Use the landlord portal') }}</a>
    </p>
@endsection
