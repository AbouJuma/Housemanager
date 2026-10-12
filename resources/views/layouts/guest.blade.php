<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    @php($brand = \App\Modules\Companies\Support\BrandTheme::current())
    @include('partials.head')
</head>
<body>
    {{-- Split screen: a photo story on the left (one of the
         public/images/brand photos, overridable per page with
         @section('auth-image', 'interior')), the form on the right. --}}
    @php($authImage = trim($__env->yieldContent('auth-image')) ?: 'villa')
    @php($thumbs = array_values(array_diff(['interior', 'apartments', 'aerial', 'villa', 'office'], [$authImage])))

    <div class="auth-split">
        <section class="auth-visual" style="--auth-image: url('{{ asset("images/brand/{$authImage}.webp") }}');">
            <a href="{{ url('/') }}" class="app-brand p-0">
                <x-brand-logo :size="44" />
                <span>
                    <span class="app-brand-name">{{ config('app.name') }}</span>
                    <span class="app-brand-sub" style="color: rgba(255,255,255,.7)">{{ __('Real estate, beautifully managed') }}</span>
                </span>
            </a>

            <div>
                <h2 class="auth-headline mb-3">@yield('auth-headline', __('Every property, lease and payment - in one place.'))</h2>
                <p class="auth-sub mb-0">@yield('auth-sub', __('Track units and occupancy, bill tenants, follow up on rent and keep landlords informed, from the office or on the go.'))</p>

                <div class="auth-thumbs" aria-hidden="true">
                    @foreach (array_slice($thumbs, 0, 3) as $thumb)
                        <span style="background-image: url('{{ asset("images/brand/{$thumb}.webp") }}');"></span>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="auth-panel">
            <div class="auth-switches">
                <x-locale-switch />
                <x-theme-switch />
            </div>

            <div class="auth-card">
                <div class="mb-4">
                    @hasSection('auth-eyebrow')
                        <div class="text-uppercase small fw-semibold mb-2" style="letter-spacing: .08em; color: var(--bs-link-color);">@yield('auth-eyebrow')</div>
                    @endif
                    <h1 class="h3 mb-1">@yield('auth-title', __('Welcome back'))</h1>
                    <p class="text-secondary mb-0">@yield('auth-subtitle', __('Sign in to continue to your workspace.'))</p>
                </div>

                @yield('content')
            </div>

            @if (request()->routeIs('login', 'portal.login', 'landlord-portal.login'))
                <div class="auth-card mt-4">
                    @include('partials.app-download')
                </div>
            @endif
        </section>
    </div>
    @include('partials.flash-toasts')
    @stack('scripts')
</body>
</html>
