@extends('layouts.app')

@section('title', $providerLabel . ' — ' . $typeLabel)

@section('page-heading')
    <h1 class="h4 mb-0">{{ $providerLabel }}</h1>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0 d-lg-none">{{ $providerLabel }}</h1>
            <p class="text-secondary mb-0">{{ $typeLabel }}</p>
        </div>
        <a href="{{ route('integrations.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> All Integrations
        </a>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form method="POST" action="{{ route('integrations.update', [$integration->type, $integration->provider]) }}">
                        @csrf
                        @method('PUT')

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" @checked(old('is_active', $integration->is_active))>
                            <label class="form-check-label" for="is_active">Enabled</label>
                        </div>

                        <div class="mb-3" style="max-width: 240px;">
                            <label class="form-label">Environment</label>
                            <select name="mode" class="form-select @error('mode') is-invalid @enderror">
                                <option value="sandbox" @selected(old('mode', $integration->mode) === 'sandbox')>Sandbox</option>
                                <option value="live" @selected(old('mode', $integration->mode) === 'live')>Production</option>
                            </select>
                            @error('mode') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        @if ($requiresCredentials)
                            <hr>
                            <h2 class="h6 mb-3">Credentials</h2>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">{{ $fieldLabels['api_key'] }}</label>
                                    <input type="text" name="api_key" class="form-control @error('api_key') is-invalid @enderror" placeholder="{{ $integration->credential('api_key') ? '•••• saved (leave blank to keep)' : '' }}">
                                    @error('api_key') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ $fieldLabels['api_secret'] }}</label>
                                    <input type="password" name="api_secret" class="form-control @error('api_secret') is-invalid @enderror" placeholder="{{ $integration->credential('api_secret') ? '•••• saved (leave blank to keep)' : '' }}">
                                    @error('api_secret') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ $fieldLabels['merchant_id'] }}</label>
                                    <input type="text" name="merchant_id" class="form-control @error('merchant_id') is-invalid @enderror" placeholder="{{ $integration->credential('merchant_id') ? '•••• saved (leave blank to keep)' : '' }}">
                                    @error('merchant_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ $fieldLabels['username'] }}</label>
                                    <input type="text" name="username" class="form-control @error('username') is-invalid @enderror" placeholder="{{ $integration->credential('username') ? '•••• saved (leave blank to keep)' : '' }}">
                                    @error('username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ $fieldLabels['password'] }}</label>
                                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="{{ $integration->credential('password') ? '•••• saved (leave blank to keep)' : '' }}">
                                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        @else
                            <hr>
                            <div class="alert alert-secondary mb-0">
                                <i class="bi bi-info-circle me-1"></i> This provider needs no credentials — nothing more to configure here.
                            </div>
                        @endif

                        @if ($requiresCredentials)
                            <hr>
                            <h2 class="h6 mb-3">URLs</h2>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Webhook URL</label>
                                    <input type="url" name="webhook_url" value="{{ old('webhook_url', $integration->webhook_url) }}" class="form-control @error('webhook_url') is-invalid @enderror">
                                    @error('webhook_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Callback URL</label>
                                    <input type="url" name="callback_url" value="{{ old('callback_url', $integration->callback_url) }}" class="form-control @error('callback_url') is-invalid @enderror">
                                    @error('callback_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        @endif

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h3 class="h6 mb-3">Connection Status</h3>

                    @if ($integration->exists)
                        <span class="badge text-bg-{{ match ($integration->status) {
                            'connected' => 'success',
                            'failed' => 'danger',
                            default => 'secondary',
                        } }} text-capitalize mb-2">{{ $integration->status }}</span>

                        @if ($integration->last_tested_at)
                            <p class="small text-secondary">Last tested {{ $integration->last_tested_at->diffForHumans() }}</p>
                        @endif

                        <form method="POST" action="{{ route('integrations.test', [$integration->type, $integration->provider]) }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-secondary w-100">Test Connection</button>
                        </form>
                    @else
                        <p class="text-secondary small mb-0">Save this integration first to test the connection.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
