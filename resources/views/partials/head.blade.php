{{-- Shared <head> tail for every layout: favicon, theme bootstrap and the
     signed-in company's brand tokens. Expects $brand (BrandTheme) from
     CompaniesServiceProvider's view composer. --}}
@php($brand ??= \App\Modules\Companies\Support\BrandTheme::current())
<link rel="icon" type="image/svg+xml" href="{{ asset('images/brand/logo-mark.svg') }}">
<link rel="apple-touch-icon" href="{{ asset('images/brand/apple-touch-icon.png') }}">
<meta name="theme-color" content="{{ $brand->primary }}">
<script>
    // Runs before first paint so a dark-mode user never sees a white flash
    // (app.js, a deferred module, only takes over after the page renders).
    (function () {
        var theme;
        try { theme = localStorage.getItem('app-theme'); } catch (e) {}
        if (theme !== 'light' && theme !== 'dark') {
            theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }
        document.documentElement.setAttribute('data-bs-theme', theme);
    })();
</script>
{{-- Values come from BrandTheme::cssVariables(), built only from
     validated #RRGGBB colors - safe to print unescaped. --}}
<style>:root { {!! $brand->cssVariables() !!} }</style>
@vite(['resources/css/app.scss', 'resources/js/app.js'])
