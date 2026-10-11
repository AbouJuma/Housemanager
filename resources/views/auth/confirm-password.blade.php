@extends('layouts.guest')

@section('title', __('Confirm Password'))
@section('auth-title', __('Confirm your password'))
@section('auth-subtitle', __('This is a secure area - please confirm it is you.'))

@section('content')
    <p class="text-secondary small mb-3">
        This is a secure area of the application. Please confirm your password before continuing.
    </p>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <div class="mb-3">
            <label for="password" class="form-label">{{ __('Password') }}</label>
            <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" required autocomplete="current-password">
            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <button type="submit" class="btn btn-primary w-100">{{ __('Confirm') }}</button>
    </form>
@endsection
