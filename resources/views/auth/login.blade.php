@extends('layouts.guest')

@section('title', __('Sign In'))
@section('auth-eyebrow', __('Staff sign in'))

@section('content')
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label">{{ __('Email, username or phone') }}</label>
            <input id="email" type="text" name="email" autocapitalize="none" spellcheck="false" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" required autofocus autocomplete="username">
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">{{ __('Password') }}</label>
            <div class="input-group">
                <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" required autocomplete="current-password">
                <button type="button" class="btn btn-outline-secondary" data-password-toggle="password" aria-label="Show password">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
            @error('password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>

        <div class="form-check mb-3">
            <input id="remember_me" type="checkbox" name="remember" class="form-check-input">
            <label for="remember_me" class="form-check-label">{{ __('Remember me') }}</label>
        </div>

        <button type="submit" class="btn btn-primary btn-lg w-100">{{ __('Log In') }}</button>

        <div class="mt-4">
            <div class="d-flex align-items-center my-3">
                <div class="flex-grow-1 border-top"></div>
                <span class="mx-2 text-uppercase small text-secondary fw-semibold">{{ __('Quick access') }}</span>
                <div class="flex-grow-1 border-top"></div>
            </div>

            <div class="row g-2">
                <div class="col-6">
                    <a href="{{ route('portal.login') }}" class="card card-hover h-100 text-decoration-none">
                        <div class="card-body d-flex align-items-center gap-2 p-3">
                            <span class="icon-tile icon-tile-sky" style="width: 36px; height: 36px;"><i class="bi bi-person-badge-fill"></i></span>
                            <span class="fw-semibold text-body small">{{ __('Tenant portal') }}</span>
                        </div>
                    </a>
                </div>
                <div class="col-6">
                    <a href="{{ route('landlord-portal.login') }}" class="card card-hover h-100 text-decoration-none">
                        <div class="card-body d-flex align-items-center gap-2 p-3">
                            <span class="icon-tile icon-tile-amber" style="width: 36px; height: 36px;"><i class="bi bi-house-door-fill"></i></span>
                            <span class="fw-semibold text-body small">{{ __('Landlord portal') }}</span>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        @if (Route::has('password.request'))
            <div class="text-end mt-3 small">
                <a href="{{ route('password.request') }}" class="text-decoration-none">{{ __('Forgot your password?') }}</a>
            </div>
        @endif
    </form>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
            const input = document.getElementById(button.dataset.passwordToggle);
            if (! input) {
                return;
            }

            button.addEventListener('click', function () {
                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                button.innerHTML = isPassword
                    ? '<i class="bi bi-eye-slash"></i>'
                    : '<i class="bi bi-eye"></i>';
                button.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
            });
        });
    });
</script>
@endpush
