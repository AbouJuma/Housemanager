<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Landlord Portal') &middot; {{ config('app.name') }}</title>
    @vite(['resources/css/app.scss', 'resources/js/app.js'])
</head>
<body>
    <nav class="navbar navbar-expand-lg border-bottom bg-body">
        <div class="container">
            <a class="navbar-brand fw-semibold" href="{{ route('landlord-portal.dashboard') }}">
                <i class="bi bi-buildings-fill me-1"></i> Landlord Portal
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#landlordPortalNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="landlordPortalNav">
                <ul class="navbar-nav me-auto gap-1">
                    <li class="nav-item"><a href="{{ route('landlord-portal.dashboard') }}" class="nav-link {{ request()->routeIs('landlord-portal.dashboard') ? 'active' : '' }}">Dashboard</a></li>
                    <li class="nav-item"><a href="{{ route('landlord-portal.properties.index') }}" class="nav-link {{ request()->routeIs('landlord-portal.properties.*') ? 'active' : '' }}">My Properties</a></li>
                    <li class="nav-item"><a href="{{ route('landlord-portal.expenses.index') }}" class="nav-link {{ request()->routeIs('landlord-portal.expenses.*') ? 'active' : '' }}">Expenses</a></li>
                    <li class="nav-item"><a href="{{ route('landlord-portal.maintenance-requests.index') }}" class="nav-link {{ request()->routeIs('landlord-portal.maintenance-requests.*') ? 'active' : '' }}">Maintenance</a></li>
                    <li class="nav-item"><a href="{{ route('landlord-portal.documents.index') }}" class="nav-link {{ request()->routeIs('landlord-portal.documents.*') ? 'active' : '' }}">Documents</a></li>
                </ul>
                <form action="{{ route('landlord-portal.logout') }}" method="POST" class="d-flex">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary btn-sm">Log Out</button>
                </form>
            </div>
        </div>
    </nav>

    <main class="container py-4">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>
