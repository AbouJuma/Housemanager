<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    @php
        $brand = \App\Modules\Companies\Support\BrandTheme::current();
    @endphp
    @include('partials.head')
</head>
<body>
    @php
        // Photo behind each section's page hero - a view can override it
        // with @section('hero-image', 'villa'). Files: public/images/brand.
        $heroImage = trim($__env->yieldContent('hero-image')) ?: match (true) {
            request()->routeIs('dashboard') => 'aerial',
            request()->routeIs('properties.*', 'buildings.*', 'units.*') => 'apartments',
            request()->routeIs('landlords.*', 'maintenance-requests.*', 'inventory-items.*') => 'villa',
            request()->routeIs('tenants.*', 'leases.*', 'visitors.*', 'documents.*') => 'interior',
            request()->routeIs('sales.*', 'buyers.*') => 'land',
            default => 'office',
        };
        $nameParts = preg_split('/\s+/', trim(auth()->user()->name));
        $initials = mb_strtoupper(mb_substr($nameParts[0], 0, 1).(count($nameParts) > 1 ? mb_substr(end($nameParts), 0, 1) : ''));
    @endphp

    <div class="app-shell">
        <aside class="app-sidebar d-none d-lg-flex flex-column p-3">
            <a href="{{ route('dashboard') }}" class="app-brand">
                <x-brand-logo :size="40" />
                <span>
                    <span class="app-brand-name">{{ $brand->companyName ?? config('app.name') }}</span>
                    <span class="app-brand-sub">{{ __('Property workspace') }}</span>
                </span>
            </a>

            @include('layouts.partials.app-nav')

            <div class="sidebar-photo-card mt-auto" style="background-image: url('{{ asset('images/brand/aerial.webp') }}');">
                <div class="small fw-semibold">{{ $brand->companyName ?? config('app.name') }}</div>
                <div class="small opacity-75">{{ __('Every property, lease and payment in one place.') }}</div>
            </div>
        </aside>

        <div class="app-main d-flex flex-column">
            <header class="app-topbar d-flex align-items-center justify-content-between gap-2 px-3 px-lg-4">
                <div class="d-flex align-items-center gap-2">
                    <button class="topbar-btn d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#appSidebarOffcanvas" aria-label="{{ __('Open menu') }}">
                        <i class="bi bi-list fs-5"></i>
                    </button>
                    <a href="{{ route('dashboard') }}" class="d-lg-none"><x-brand-logo :size="34" /></a>
                    <button class="topbar-btn d-none d-md-inline-flex" type="button" data-bs-toggle="modal" data-bs-target="#commandPaletteModal" title="{{ __('Search') }} (Ctrl+K)" style="min-width: 260px; justify-content: flex-start;">
                        <i class="bi bi-search text-secondary"></i>
                        <span class="text-secondary small flex-grow-1 text-start">{{ __('Search…') }}</span>
                        <kbd class="small text-secondary bg-transparent border px-1">Ctrl K</kbd>
                    </button>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <button class="topbar-btn d-md-none" type="button" data-bs-toggle="modal" data-bs-target="#commandPaletteModal" aria-label="{{ __('Search') }}">
                        <i class="bi bi-search"></i>
                    </button>

                    <x-locale-switch class="d-none d-sm-inline-flex" />
                    <x-theme-switch />

                    <div class="dropdown">
                        <button class="topbar-btn position-relative" type="button" id="notificationBellButton" data-bs-toggle="dropdown" aria-label="{{ __('Notifications') }}">
                            <i class="bi bi-bell"></i>
                            <span id="notificationUnreadBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill text-bg-danger d-none">0</span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end p-0" style="width: 340px;" id="notificationDropdown">
                            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                                <span class="fw-semibold small">{{ __('Notifications') }}</span>
                                <a href="{{ route('notifications.index') }}" class="small">{{ __('View all') }}</a>
                            </div>
                            <div id="notificationList" class="list-group list-group-flush" style="max-height: 360px; overflow-y: auto;">
                                <div class="list-group-item text-secondary small">{{ __('Loading…') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="dropdown">
                        <button class="topbar-btn ps-1 pe-2" type="button" data-bs-toggle="dropdown" aria-label="{{ __('Account menu') }}">
                            <span class="avatar">{{ $initials }}</span>
                            <span class="d-none d-md-inline small fw-medium">{{ auth()->user()->name }}</span>
                            <i class="bi bi-chevron-down small text-secondary"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li class="px-3 py-2">
                                <div class="fw-semibold small">{{ auth()->user()->name }}</div>
                                <div class="text-secondary small">{{ auth()->user()->email }}</div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person-gear me-2"></i>{{ __('Profile') }}</a></li>
                            @if (! auth()->user()->isSaasOwner() && auth()->user()->can('settings.view-company'))
                                <li><a class="dropdown-item" href="{{ route('company-profile.edit') }}"><i class="bi bi-building-gear me-2"></i>{{ __('Company Profile') }}</a></li>
                                <li><a class="dropdown-item" href="{{ route('branding.edit') }}"><i class="bi bi-palette me-2"></i>{{ __('Branding') }}</a></li>
                            @endif
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="bi bi-box-arrow-right me-2"></i>{{ __('Log Out') }}
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </header>

            <main class="flex-grow-1 p-3 p-lg-4">
                @hasSection('page-heading')
                    <section class="page-hero @yield('hero-class')" style="--hero-image: url('{{ asset("images/brand/{$heroImage}.webp") }}');">
                        @yield('page-heading')
                    </section>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <div class="offcanvas offcanvas-start d-lg-none app-sidebar-offcanvas" tabindex="-1" id="appSidebarOffcanvas">
        <div class="offcanvas-header">
            <a href="{{ route('dashboard') }}" class="app-brand p-0">
                <x-brand-logo :size="34" />
                <span class="app-brand-name">{{ $brand->companyName ?? config('app.name') }}</span>
            </a>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body pt-0">
            <x-locale-switch class="mb-3 d-sm-none" />
            @include('layouts.partials.app-nav')
        </div>
    </div>

    <div class="modal fade" id="commandPaletteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body p-0">
                    <div class="p-3 border-bottom d-flex align-items-center gap-2">
                        <i class="bi bi-search text-secondary"></i>
                        <input type="text" id="commandPaletteInput" class="form-control form-control-lg border-0 shadow-none px-0" placeholder="{{ __('Search companies, users…') }}" autocomplete="off">
                    </div>
                    <div id="commandPaletteResults" class="list-group list-group-flush" style="max-height: 360px; overflow-y: auto;"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modals that belong to a form partial but must not be nested
         inside that <form> (their own inputs would join its validation). --}}
    @stack('modals')

    @include('partials.flash-toasts')
    @stack('scripts')
</body>
</html>
