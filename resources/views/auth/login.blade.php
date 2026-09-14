@extends('layouts.guest')

@section('title', 'Sign In')

@section('content')
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" required autofocus autocomplete="username">
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
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
            <label for="remember_me" class="form-check-label">Remember me</label>
        </div>

        <button type="submit" class="btn btn-primary w-100">Log In</button>

        <div class="mt-4">
            <div class="d-flex align-items-center my-3">
                <div class="flex-grow-1 border-top"></div>
                <span class="mx-2 text-uppercase small text-secondary fw-semibold">Quick access</span>
                <div class="flex-grow-1 border-top"></div>
            </div>

            <div class="d-grid gap-2">
                <a href="{{ route('portal.login') }}" class="btn btn-outline-primary btn-lg d-flex align-items-center justify-content-center gap-2">
                    <i class="bi bi-person-badge-fill"></i>
                    <span>Login as Tenant</span>
                </a>

                <a href="{{ route('landlord-portal.login') }}" class="btn btn-outline-success btn-lg d-flex align-items-center justify-content-center gap-2">
                    <i class="bi bi-house-door-fill"></i>
                    <span>Login as Landlord</span>
                </a>
            </div>
        </div>

        @if (Route::has('password.request'))
            <div class="text-end mt-3 small">
                <a href="{{ route('password.request') }}" class="text-decoration-none">Forgot your password?</a>
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
