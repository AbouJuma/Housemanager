@extends('layouts.app')

@section('title', __('System Settings'))

@section('page-heading')
    <h1 class="h4 mb-0">{{ __('System Settings') }}</h1>
@endsection

@section('content')
    <div class="card border-0 shadow-sm" style="max-width: 480px;">
        <div class="card-body">
            <form method="POST" action="{{ route('settings.system.update') }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="default_trial_days" class="form-label">Default Trial Length (days)</label>
                    <input id="default_trial_days" type="number" min="0" max="365" name="default_trial_days" value="{{ old('default_trial_days', $values['default_trial_days']) }}" class="form-control @error('default_trial_days') is-invalid @enderror" required>
                    <div class="form-text">How many days a new company's trial lasts before it must be activated.</div>
                    @error('default_trial_days') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label for="default_currency_code" class="form-label">Default Currency</label>
                    <input id="default_currency_code" type="text" maxlength="3" name="default_currency_code" value="{{ old('default_currency_code', $values['default_currency_code']) }}" class="form-control text-uppercase @error('default_currency_code') is-invalid @enderror" required>
                    <div class="form-text">ISO 4217 code assigned to a new company when none is given at creation.</div>
                    @error('default_currency_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <button type="submit" class="btn btn-primary">{{ __('Save Changes') }}</button>
            </form>
        </div>
    </div>
@endsection
