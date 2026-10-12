{{-- "Get the Android app" card under the sign-in forms - shown only when
     an APK has been published (super admin -> Mobile App). Always links
     to the newest version. --}}
@php
    $appRelease = \App\Core\Support\AppRelease::latest();
@endphp

@if ($appRelease)
    <a href="{{ route('app.download') }}" class="app-download-card" download>
        <span class="app-download-icon" aria-hidden="true"><i class="bi bi-android2"></i></span>
        <span class="app-download-text">
            <span class="app-download-eyebrow">{{ __('Get the app') }}</span>
            <span class="app-download-title">{{ __('Download for Android') }}</span>
            <span class="app-download-meta">
                {{ $appRelease['version'] ? __('Version :version', ['version' => $appRelease['version']]).' · ' : '' }}{{ $appRelease['size_mb'] }} MB · {{ __('Updated :date', ['date' => $appRelease['uploaded_at']->timezone(company_timezone())->format('d M Y')]) }}
            </span>
        </span>
        <span class="app-download-arrow" aria-hidden="true"><i class="bi bi-download"></i></span>
    </a>
    <p class="app-download-hint">{{ __('After downloading, open the file and allow installing from this source if your phone asks.') }}</p>
@endif
