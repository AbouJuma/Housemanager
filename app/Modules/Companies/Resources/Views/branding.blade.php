@extends('layouts.app')

@section('title', __('Branding'))
@section('hero-image', 'villa')

@section('page-heading')
    <div class="page-hero-eyebrow">{{ __('Settings') }}</div>
    <h1 class="h3 mb-1">{{ __('Branding') }}</h1>
    <p class="text-secondary mb-0">Your logo and colors - used across the back office, the tenant &amp; landlord portals and the mobile app.</p>
@endsection

@section('content')
    <form method="POST" action="{{ route('branding.update') }}" enctype="multipart/form-data" id="brandingForm">
        @csrf
        @method('PUT')

        <div class="row g-4">
            <div class="col-xl-7">
                <div class="card mb-4">
                    <div class="card-header d-flex align-items-center gap-2">
                        <span class="icon-tile icon-tile-violet" style="width: 32px; height: 32px; font-size: 1rem;"><i class="bi bi-palette"></i></span>
                        {{ __('Colors') }}
                    </div>
                    <div class="card-body">
                        <p class="text-secondary small mb-2">Start from a preset…</p>
                        <div class="d-flex flex-wrap gap-2 mb-4">
                            @foreach ($presets as $name => [$primary, $accent])
                                <button type="button" class="preset-swatch" data-primary="{{ $primary }}" data-accent="{{ $accent }}">
                                    <span class="dots"><i style="background: {{ $primary }}"></i><i style="background: {{ $accent }}"></i></span>
                                    {{ $name }}
                                </button>
                            @endforeach
                        </div>

                        <p class="text-secondary small mb-2">…or pick your own.</p>
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label" for="primaryColorText">{{ __('Primary color') }}</label>
                                <div class="input-group">
                                    <input type="color" id="primaryColorPicker" value="{{ old('primary_color', $theme->primary) }}" class="form-control form-control-color" style="max-width: 56px;" aria-label="Primary color picker">
                                    <input type="text" id="primaryColorText" name="primary_color" value="{{ old('primary_color', $theme->primary) }}" class="form-control font-monospace text-uppercase @error('primary_color') is-invalid @enderror" maxlength="7" pattern="#[0-9A-Fa-f]{6}">
                                    @error('primary_color') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="form-text">{{ __('Buttons, active menu items, links and headers.') }}</div>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label" for="accentColorText">{{ __('Accent color') }}</label>
                                <div class="input-group">
                                    <input type="color" id="accentColorPicker" value="{{ old('secondary_color', $theme->accent) }}" class="form-control form-control-color" style="max-width: 56px;" aria-label="Accent color picker">
                                    <input type="text" id="accentColorText" name="secondary_color" value="{{ old('secondary_color', $theme->accent) }}" class="form-control font-monospace text-uppercase @error('secondary_color') is-invalid @enderror" maxlength="7" pattern="#[0-9A-Fa-f]{6}">
                                    @error('secondary_color') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="form-text">{{ __('Highlights and small details.') }}</div>
                            </div>
                        </div>
                        <div id="contrastWarning" class="alert alert-warning small mt-3 mb-0 d-none">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            This primary color is very light - button text will switch to dark so it stays readable.
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header d-flex align-items-center gap-2">
                        <span class="icon-tile icon-tile-sky" style="width: 32px; height: 32px; font-size: 1rem;"><i class="bi bi-image"></i></span>
                        {{ __('Logo') }}
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <x-brand-logo :size="64" />
                            <div class="small text-secondary">
                                @if ($company->logoUrl())
                                    Your uploaded logo. A square image works best.
                                @else
                                    No logo uploaded - the default house mark is shown in your primary color.
                                @endif
                            </div>
                        </div>
                        <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" class="form-control @error('logo') is-invalid @enderror">
                        @error('logo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">PNG, JPG or WebP, up to 2 MB. Square, at least 256&times;256.</div>
                        @if ($company->logoUrl())
                            <div class="form-check mt-3">
                                <input class="form-check-input" type="checkbox" name="remove_logo" value="1" id="removeLogo">
                                <label class="form-check-label" for="removeLogo">{{ __('Remove the current logo') }}</label>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 mt-4">
                    <button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-check2 me-1"></i> {{ __('Save branding') }}</button>
                    @can('settings.update-company')
                        <button type="submit" name="reset" value="1" class="btn btn-outline-secondary btn-lg" formnovalidate onclick="return confirm('Reset colors to the default palette?')">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> {{ __('Reset to default') }}
                        </button>
                    @endcan
                </div>
            </div>

            <div class="col-xl-5">
                <div class="card position-sticky" style="top: calc(var(--app-topbar-height) + 1rem);">
                    <div class="card-header">{{ __('Live preview') }}</div>
                    <div class="card-body" id="brandPreview">
                        <div class="rounded-4 overflow-hidden border" style="border-color: var(--app-surface-border) !important;">
                            <div class="d-flex" style="min-height: 260px;">
                                <div class="p-3 d-flex flex-column gap-2" style="width: 42%; background: radial-gradient(120% 60% at 0% 0%, rgba(var(--brand-rgb), .35), transparent 60%), var(--ink);">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <span class="rounded-3 d-inline-block" style="width: 22px; height: 22px; background: linear-gradient(135deg, var(--brand-light), var(--brand));"></span>
                                        <span class="small fw-semibold text-white text-truncate">{{ $company->name }}</span>
                                    </div>
                                    <span class="small rounded-2 px-2 py-1" style="background: linear-gradient(135deg, var(--brand), var(--brand-hover)); color: var(--brand-contrast);"><i class="bi bi-grid-1x2 me-1"></i> {{ __('Dashboard') }}</span>
                                    <span class="small px-2 py-1" style="color: rgba(255,255,255,.7);"><i class="bi bi-buildings me-1"></i> {{ __('Properties') }}</span>
                                    <span class="small px-2 py-1" style="color: rgba(255,255,255,.7);"><i class="bi bi-people me-1"></i> {{ __('Tenants') }}</span>
                                    <span class="small px-2 py-1" style="color: rgba(255,255,255,.7);"><i class="bi bi-receipt me-1"></i> {{ __('Invoices') }}</span>
                                </div>
                                <div class="p-3 flex-grow-1 bg-body-tertiary">
                                    <div class="page-hero mb-3 p-3" style="min-height: 0; --hero-image: url('{{ asset('images/brand/aerial.webp') }}');">
                                        <div class="page-hero-eyebrow" style="font-size: .6rem;">{{ __('Today') }}</div>
                                        <div class="fw-semibold">{{ __('Good morning') }}</div>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2 mb-3">
                                        <span class="btn btn-primary btn-sm">{{ __('Primary') }}</span>
                                        <span class="btn btn-outline-primary btn-sm">{{ __('Outline') }}</span>
                                        <span class="badge bg-accent align-self-center">{{ __('New') }}</span>
                                    </div>
                                    <a href="#" class="small" onclick="return false;">{{ __('A sample link') }}</a>
                                    <div class="progress mt-3" style="height: 6px;"><div class="progress-bar" style="width: 68%"></div></div>
                                </div>
                            </div>
                        </div>
                        <p class="small text-secondary mt-3 mb-0">The preview updates as you pick - nothing changes for your team until you save.</p>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const preview = document.getElementById('brandPreview');
        const primaryPicker = document.getElementById('primaryColorPicker');
        const primaryText = document.getElementById('primaryColorText');
        const accentPicker = document.getElementById('accentColorPicker');
        const accentText = document.getElementById('accentColorText');
        const contrastWarning = document.getElementById('contrastWarning');
        const isHex = (value) => /^#[0-9A-Fa-f]{6}$/.test(value);

        function rgb(hex) {
            return [1, 3, 5].map((i) => parseInt(hex.slice(i, i + 2), 16));
        }

        // Same math as App\Modules\Companies\Support\BrandTheme.
        function shade(hex, amount) {
            const target = amount > 0 ? 255 : 0;
            const a = Math.abs(amount);
            return '#' + rgb(hex).map((c) => Math.round(c + (target - c) * a).toString(16).padStart(2, '0')).join('');
        }

        function luminance(hex) {
            const [r, g, b] = rgb(hex).map((c) => {
                c /= 255;
                return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
            });
            return 0.2126 * r + 0.7152 * g + 0.0722 * b;
        }

        function contrastText(hex) {
            const l = luminance(hex);
            return (1.05 / (l + 0.05)) >= ((l + 0.05) / 0.0591) ? '#FFFFFF' : '#101828';
        }

        function apply() {
            const primary = isHex(primaryText.value) ? primaryText.value : primaryPicker.value;
            const accent = isHex(accentText.value) ? accentText.value : accentPicker.value;
            const light = shade(primary, 0.45);

            preview.style.setProperty('--brand', primary);
            preview.style.setProperty('--brand-rgb', rgb(primary).join(', '));
            preview.style.setProperty('--brand-hover', shade(primary, -0.14));
            preview.style.setProperty('--brand-light', light);
            preview.style.setProperty('--brand-light-rgb', rgb(light).join(', '));
            preview.style.setProperty('--brand-contrast', contrastText(primary));
            preview.style.setProperty('--accent', accent);
            preview.style.setProperty('--accent-rgb', rgb(accent).join(', '));
            preview.style.setProperty('--accent-contrast', contrastText(accent));
            // Bootstrap's own primary vars resolve on :root, so re-point
            // them here too for the preview's links/progress bar.
            preview.style.setProperty('--bs-primary-rgb', rgb(primary).join(', '));
            preview.style.setProperty('--bs-link-color', primary);
            preview.style.setProperty('--bs-link-color-rgb', rgb(primary).join(', '));

            contrastWarning.classList.toggle('d-none', contrastText(primary) === '#FFFFFF');
        }

        function sync(picker, text) {
            picker.addEventListener('input', () => { text.value = picker.value.toUpperCase(); apply(); });
            text.addEventListener('input', () => {
                if (isHex(text.value)) {
                    picker.value = text.value;
                }
                apply();
            });
        }

        sync(primaryPicker, primaryText);
        sync(accentPicker, accentText);

        document.querySelectorAll('.preset-swatch').forEach((button) => {
            button.addEventListener('click', () => {
                primaryPicker.value = primaryText.value = button.dataset.primary;
                accentPicker.value = accentText.value = button.dataset.accent;
                apply();
            });
        });

        apply();
    });
</script>
@endpush
