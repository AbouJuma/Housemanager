@extends('layouts.app')

@section('title', __('Integrations'))

@section('page-heading')
    <h1 class="h4 mb-0">{{ __('Integrations') }}</h1>
@endsection

@section('content')
    <h1 class="h4 mb-3 d-lg-none">{{ __('Integrations') }}</h1>

    @foreach ($types as $typeKey => $type)
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h2 class="h6 mb-3"><i class="bi {{ $type['icon'] }} me-1"></i> {{ $type['label'] }}</h2>
                <div class="row g-3">
                    @foreach ($type['providers'] as $providerKey => $providerLabel)
                        @php($integration = $configured->get("{$typeKey}.{$providerKey}"))
                        <div class="col-md-4 col-xl-3">
                            <a href="{{ route('integrations.edit', [$typeKey, $providerKey]) }}" class="card border h-100 text-decoration-none text-body">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <span class="fw-semibold">{{ $providerLabel }}</span>
                                        @if ($integration && $integration->is_active)
                                            <span class="badge text-bg-success">{{ __('Enabled') }}</span>
                                        @elseif ($integration)
                                            <span class="badge text-bg-secondary">{{ __('Disabled') }}</span>
                                        @else
                                            <span class="badge text-bg-light text-secondary border">{{ __('Not Configured') }}</span>
                                        @endif
                                    </div>
                                    @if ($integration)
                                        <div class="small text-secondary mt-2 text-capitalize">{{ $integration->status }} &middot; {{ $integration->mode }}</div>
                                    @endif
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach
@endsection
