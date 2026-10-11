<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __($portalName)) &middot; {{ config('app.name') }}</title>
    @php
        $brand = \App\Modules\Companies\Support\BrandTheme::current();
    @endphp
    @include('partials.head')
</head>
<body>
    {{-- Shared chrome for the tenant and landlord portals - each portal's
         own layout.blade.php extends this, passing $portalName, $homeRoute,
         $logoutRoute, $guard, $heroImage and $navItems
         ([label, route, active pattern, icon]). --}}
    @php
        $portalUser = auth($guard)->user();
    @endphp

    <nav class="navbar navbar-expand-lg portal-nav py-2">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2 me-4" href="{{ route($homeRoute) }}">
                <x-brand-logo :size="36" />
                <span class="lh-sm">
                    <span class="d-block fw-bold text-body" style="letter-spacing: -.02em;">{{ $brand->companyName ?? config('app.name') }}</span>
                    <span class="d-block small text-secondary">{{ __($portalName) }}</span>
                </span>
            </a>
            <div class="d-flex align-items-center gap-2 order-lg-last">
                <x-locale-switch />
                <x-theme-switch />
                <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#portalNav" aria-label="{{ __('Menu') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>
            <div class="collapse navbar-collapse" id="portalNav">
                <ul class="navbar-nav me-auto gap-1 py-2 py-lg-0">
                    @foreach ($navItems as [$label, $route, $pattern, $icon])
                        <li class="nav-item">
                            <a href="{{ route($route) }}" class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs($pattern) ? 'active' : '' }}">
                                <i class="bi {{ $icon }}"></i> {{ __($label) }}
                            </a>
                        </li>
                    @endforeach
                </ul>
                <div class="d-flex align-items-center gap-2 me-lg-2">
                    @if ($portalUser)
                        <span class="avatar">{{ mb_strtoupper(collect(preg_split('/\s+/', trim($portalUser->name)))->pipe(fn ($p) => mb_substr($p->first(), 0, 1).($p->count() > 1 ? mb_substr($p->last(), 0, 1) : ''))) }}</span>
                        <span class="small fw-medium d-lg-none d-xl-inline">{{ $portalUser->name }}</span>
                    @endif
                    <form action="{{ route($logoutRoute) }}" method="POST" class="d-flex">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary btn-sm"><i class="bi bi-box-arrow-right me-1"></i>{{ __('Log Out') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <main class="container py-4">
        {{-- Breadcrumbs: Dashboard > section > this page, worked out from
             the portal's own menu and the page title. A page can supply
             its own trail with @section('breadcrumbs'). --}}
        @if (! request()->routeIs($homeRoute))
            @hasSection('breadcrumbs')
                @yield('breadcrumbs')
            @else
                @php
                    $trail = collect([['label' => __('Dashboard'), 'url' => route($homeRoute)]]);
                    $section = collect($navItems)->first(fn ($item) => $item[1] !== $homeRoute && request()->routeIs($item[2]));
                    if ($section) {
                        $onSectionIndex = request()->routeIs($section[1]);
                        $trail->push(['label' => __($section[0]), 'url' => $onSectionIndex ? null : route($section[1])]);
                        if (! $onSectionIndex) {
                            $trail->push(['label' => trim($__env->yieldContent('title')) ?: __('Details')]);
                        }
                    } else {
                        $trail->push(['label' => trim($__env->yieldContent('title')) ?: __('Details')]);
                    }
                @endphp
                <x-breadcrumb :items="$trail" />
            @endif
        @endif

        @hasSection('portal-hero')
            <section class="page-hero page-hero-lg" style="--hero-image: url('{{ asset("images/brand/{$heroImage}.webp") }}');">
                @yield('portal-hero')
            </section>
        @endif

        @yield('content')
    </main>

    <footer class="container pb-4 small text-secondary d-flex justify-content-between">
        <span>&copy; {{ now()->year }} {{ $brand->companyName ?? config('app.name') }}</span>
        <span>{{ __($portalName) }}</span>
    </footer>

    @stack('modals')
    @include('partials.flash-toasts')
    @stack('scripts')
</body>
</html>
