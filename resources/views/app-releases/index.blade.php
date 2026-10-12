@extends('layouts.app')

@section('title', __('Mobile App'))

@section('page-heading')
    <h1 class="h4 mb-0">{{ __('Mobile App') }}</h1>
@endsection

@section('content')
    @php
        $latest = $releases[0] ?? null;
    @endphp

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 mb-3"><i class="bi bi-cloud-arrow-up me-1"></i> {{ __('Publish a new version') }}</h2>
                    <p class="text-secondary small">
                        {{ __('Upload the release APK. It replaces the download under every sign-in page straight away.') }}
                    </p>
                    <form method="POST" action="{{ route('app-releases.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">{{ __('Version') }}</label>
                            <input type="text" name="version" value="{{ old('version') }}" class="form-control @error('version') is-invalid @enderror" placeholder="1.0.2" required style="max-width: 10rem;">
                            @error('version') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('APK file') }}</label>
                            <input type="file" name="apk" accept=".apk" class="form-control @error('apk') is-invalid @enderror" required>
                            @error('apk') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">{{ __('This server accepts uploads up to :size MB.', ['size' => $maxUploadMb]) }}</div>
                        </div>
                        <button class="btn btn-primary"><i class="bi bi-upload me-1"></i> {{ __('Publish') }}</button>
                    </form>
                    <hr>
                    <p class="small text-secondary mb-0">
                        {{ __('File too big for the upload? Copy it with the hosting File Manager into') }}
                        <code>storage/app/releases/</code> {{ __('named like') }} <code>HouseManagers-v1.0.2.apk</code> {{ __('- the newest file is always the one offered.') }}
                    </p>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 mb-3"><i class="bi bi-android2 me-1"></i> {{ __('Available now') }}</h2>
                    @if (! $latest)
                        <p class="text-secondary mb-0">{{ __('No APK published yet - the sign-in pages show no download until you add one.') }}</p>
                    @else
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="rounded-4 d-flex align-items-center justify-content-center text-white" style="width: 56px; height: 56px; background: var(--brand);">
                                <i class="bi bi-phone fs-3"></i>
                            </div>
                            <div>
                                <div class="fw-semibold">{{ __('Version') }} {{ $latest['version'] ?? '—' }}</div>
                                <div class="small text-secondary">{{ $latest['size_mb'] }} MB · {{ $latest['uploaded_at']->timezone(company_timezone())->format('d M Y, H:i') }}</div>
                            </div>
                        </div>
                        <div class="input-group input-group-sm mb-3">
                            <input type="text" class="form-control" value="{{ route('app.download') }}" readonly id="appDownloadLink">
                            <button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('appDownloadLink').value)">
                                <i class="bi bi-clipboard"></i> {{ __('Copy link') }}
                            </button>
                        </div>
                        <p class="small text-secondary">{{ __('Share this link anywhere (WhatsApp, SMS) - it always gives the newest version.') }}</p>

                        @if (count($releases) > 1)
                            <h3 class="h6 mt-4">{{ __('Earlier versions kept') }}</h3>
                            <ul class="list-unstyled small mb-0">
                                @foreach (array_slice($releases, 1) as $old)
                                    <li class="text-secondary">{{ $old['file'] }} · {{ $old['size_mb'] }} MB</li>
                                @endforeach
                            </ul>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
