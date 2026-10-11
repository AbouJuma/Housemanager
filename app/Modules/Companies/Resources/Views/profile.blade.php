@extends('layouts.app')

@section('title', __('Company Profile'))
@section('hero-image', 'office')

@section('page-heading')
    <div class="page-hero-eyebrow">{{ __('Settings') }}</div>
    <h1 class="h3 mb-1">{{ __('Company Profile') }}</h1>
    <p class="text-secondary mb-0">Your company's name and contact details, as shown on invoices, messages and the tenant &amp; landlord portals.</p>
@endsection

@section('content')
    @php
        $canEdit = auth()->user()->can('settings.update-company');
        $allTimezones = array_diff(timezone_identifiers_list(), array_keys($commonTimezones));
        $selectedTimezone = old('timezone', $company->timezone);
    @endphp

    <form method="POST" action="{{ route('company-profile.update') }}">
        @csrf
        @method('PUT')

        <div class="row g-4">
            <div class="col-xl-8">
                <div class="card mb-4">
                    <div class="card-header d-flex align-items-center gap-2">
                        <span class="icon-tile icon-tile-brand" style="width: 32px; height: 32px; font-size: 1rem;"><i class="bi bi-building"></i></span>
                        {{ __('Company details') }}
                    </div>
                    <div class="card-body">
                        <fieldset @disabled(! $canEdit)>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label" for="name">{{ __('Company name') }} <span class="text-danger">*</span></label>
                                    <input type="text" id="name" name="name" value="{{ old('name', $company->name) }}" class="form-control form-control-lg @error('name') is-invalid @enderror" required>
                                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="email">{{ __('Email') }}</label>
                                    <input type="email" id="email" name="email" value="{{ old('email', $company->email) }}" class="form-control @error('email') is-invalid @enderror" placeholder="info@yourcompany.co.tz">
                                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="phone">{{ __('Phone') }}</label>
                                    <input type="text" id="phone" name="phone" value="{{ old('phone', $company->phone) }}" class="form-control @error('phone') is-invalid @enderror" placeholder="+255 7xx xxx xxx">
                                    @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="website">{{ __('Website') }}</label>
                                    <input type="url" id="website" name="website" value="{{ old('website', $company->website) }}" class="form-control @error('website') is-invalid @enderror" placeholder="https://yourcompany.co.tz">
                                    @error('website') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </fieldset>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header d-flex align-items-center gap-2">
                        <span class="icon-tile icon-tile-sky" style="width: 32px; height: 32px; font-size: 1rem;"><i class="bi bi-geo-alt"></i></span>
                        {{ __('Address') }}
                    </div>
                    <div class="card-body">
                        <fieldset @disabled(! $canEdit)>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="address_line1">Address line 1</label>
                                    <input type="text" id="address_line1" name="address_line1" value="{{ old('address_line1', $company->address_line1) }}" class="form-control @error('address_line1') is-invalid @enderror">
                                    @error('address_line1') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="address_line2">Address line 2</label>
                                    <input type="text" id="address_line2" name="address_line2" value="{{ old('address_line2', $company->address_line2) }}" class="form-control @error('address_line2') is-invalid @enderror">
                                    @error('address_line2') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="state">{{ __('Region') }}</label>
                                    <input type="text" id="state" name="state" list="companyRegions" value="{{ old('state', $company->state) }}" class="form-control @error('state') is-invalid @enderror" placeholder="e.g. Dar es Salaam">
                                    <datalist id="companyRegions">
                                        @foreach ($regions as $region)
                                            <option value="{{ $region }}">
                                        @endforeach
                                    </datalist>
                                    @error('state') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="city">{{ __('City / District') }}</label>
                                    <input type="text" id="city" name="city" value="{{ old('city', $company->city) }}" class="form-control @error('city') is-invalid @enderror">
                                    @error('city') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label" for="postal_code">{{ __('Postal code') }}</label>
                                    <input type="text" id="postal_code" name="postal_code" value="{{ old('postal_code', $company->postal_code) }}" class="form-control @error('postal_code') is-invalid @enderror">
                                    @error('postal_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label" for="country_code">{{ __('Country') }}</label>
                                    <input type="text" id="country_code" name="country_code" maxlength="2" value="{{ old('country_code', $company->country_code ?? 'TZ') }}" class="form-control text-uppercase @error('country_code') is-invalid @enderror">
                                    @error('country_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </fieldset>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card mb-4">
                    <div class="card-header d-flex align-items-center gap-2">
                        <span class="icon-tile icon-tile-amber" style="width: 32px; height: 32px; font-size: 1rem;"><i class="bi bi-clock"></i></span>
                        {{ __('Regional settings') }}
                    </div>
                    <div class="card-body">
                        <fieldset @disabled(! $canEdit)>
                            <div class="mb-3">
                                <label class="form-label" for="timezone">{{ __('Timezone') }}</label>
                                <select id="timezone" name="timezone" class="form-select @error('timezone') is-invalid @enderror">
                                    <optgroup label="Common">
                                        @foreach ($commonTimezones as $zone => $label)
                                            <option value="{{ $zone }}" @selected($selectedTimezone === $zone)>{{ $label }}</option>
                                        @endforeach
                                    </optgroup>
                                    <optgroup label="All timezones">
                                        @foreach ($allTimezones as $zone)
                                            <option value="{{ $zone }}" @selected($selectedTimezone === $zone)>{{ str_replace('_', ' ', $zone) }}</option>
                                        @endforeach
                                    </optgroup>
                                </select>
                                @error('timezone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <div class="form-text">
                                    Used for greetings, "today" and dates. It's now
                                    <strong>{{ now()->setTimezone($company->timezone ?: config('company.default_timezone'))->format('g:i A') }}</strong> there.
                                </div>
                            </div>
                            <div>
                                <label class="form-label" for="currency_code">{{ __('Currency') }}</label>
                                <select id="currency_code" name="currency_code" class="form-select @error('currency_code') is-invalid @enderror">
                                    @foreach ($currencies as $code => $label)
                                        <option value="{{ $code }}" @selected(old('currency_code', $company->currency_code) === $code)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('currency_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </fieldset>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <x-brand-logo :size="52" />
                        <div class="flex-grow-1">
                            <div class="fw-semibold">{{ __('Logo &amp; colors') }}</div>
                            <div class="small text-secondary">{{ __('Change how your workspace looks.') }}</div>
                        </div>
                        <a href="{{ route('branding.edit') }}" class="btn btn-outline-primary btn-sm">{{ __('Branding') }}</a>
                    </div>
                </div>
            </div>
        </div>

        @if ($canEdit)
            <div class="mt-4">
                <button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-check2 me-1"></i> {{ __('Save changes') }}</button>
            </div>
        @else
            <p class="text-secondary small mt-4 mb-0"><i class="bi bi-lock me-1"></i> You can view these details; ask an administrator to change them.</p>
        @endif
    </form>
@endsection
