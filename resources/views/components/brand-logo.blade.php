@props(['size' => 40])
@php($brand ??= \App\Modules\Companies\Support\BrandTheme::current())

{{-- The company's uploaded logo if it has one, otherwise the default
     house mark drawn in the company's own brand color. --}}
@if ($brand->logoUrl)
    <img src="{{ $brand->logoUrl }}" alt="{{ $brand->companyName ?? config('app.name') }} logo" width="{{ $size }}" height="{{ $size }}" {{ $attributes->merge(['class' => 'brand-logo bg-white']) }}>
@else
    <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 64 64" aria-hidden="true" {{ $attributes->merge(['class' => 'brand-logo']) }}>
        <defs>
            <linearGradient id="brandLogoGradient{{ $size }}" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0" style="stop-color: var(--brand-light)"/>
                <stop offset=".45" style="stop-color: var(--brand)"/>
                <stop offset="1" style="stop-color: var(--brand-hover)"/>
            </linearGradient>
        </defs>
        <rect width="64" height="64" rx="16" fill="url(#brandLogoGradient{{ $size }})"/>
        <circle cx="47.5" cy="14.5" r="4.5" style="fill: var(--accent)"/>
        <path d="M13 32 32 16.5 51 32" fill="none" stroke="#fff" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M19 26v20a4 4 0 0 0 4 4h18a4 4 0 0 0 4-4V26" fill="none" stroke="#fff" stroke-width="5" stroke-linejoin="round"/>
        <rect x="28" y="37" width="8" height="13" rx="2" fill="#fff"/>
    </svg>
@endif
