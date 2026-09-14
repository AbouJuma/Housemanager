<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    @vite(['resources/css/app.scss', 'resources/js/app.js'])
</head>
<body>
    <div class="auth-shell">
        <div class="auth-card">
            <div class="text-center mb-4">
                <a href="{{ route('dashboard') }}" class="text-decoration-none">
                    <i class="bi bi-buildings-fill fs-1 text-primary"></i>
                    <div class="fs-5 fw-semibold text-body">{{ config('app.name') }}</div>
                </a>
            </div>

            <div class="card shadow">
                <div class="card-body p-4">
                    @yield('content')
                </div>
            </div>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
