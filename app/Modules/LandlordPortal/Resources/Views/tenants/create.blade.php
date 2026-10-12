@extends('landlord-portal::layout')

@section('title', __('Add Tenant'))

@section('content')
    <h1 class="h4 mb-4">{{ __('Add Tenant') }}</h1>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('landlord-portal.tenants.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Full name') }}</label>
                        <input type="text" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Type') }}</label>
                        <select name="type" class="form-select @error('type') is-invalid @enderror">
                            <option value="individual" @selected(old('type', 'individual') === 'individual')>{{ __('Individual') }}</option>
                            <option value="company" @selected(old('type') === 'company')>{{ __('Company') }}</option>
                        </select>
                        @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Phone') }}</label>
                        <input type="tel" name="phone" value="{{ old('phone') }}" class="form-control @error('phone') is-invalid @enderror" placeholder="07XX XXX XXX" required>
                        <div class="form-text">{{ __('The tenant signs in with this number, and it is their first password.') }}</div>
                        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Email') }} <span class="text-secondary fw-normal">{{ __('(optional)') }}</span></label>
                        <input type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror">
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Username') }} <span class="text-secondary fw-normal">{{ __('(optional)') }}</span></label>
                        <input type="text" name="username" value="{{ old('username') }}" class="form-control @error('username') is-invalid @enderror" autocomplete="off">
                        <div class="form-text">{{ __('Leave blank to create one from the name.') }}</div>
                        @error('username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('ID type') }}</label>
                        <select name="identification_type" class="form-select @error('identification_type') is-invalid @enderror">
                            <option value="">—</option>
                            @foreach (['national_id' => 'National ID', 'passport' => 'Passport', 'driving_license' => 'Driving licence', 'voter_id' => 'Voter ID', 'other' => 'Other'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('identification_type') === $value)>{{ __($label) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('ID number') }}</label>
                        <input type="text" name="identification_number" value="{{ old('identification_number') }}" class="form-control @error('identification_number') is-invalid @enderror">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Emergency contact') }}</label>
                        <input type="text" name="emergency_contact_name" value="{{ old('emergency_contact_name') }}" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Emergency contact phone') }}</label>
                        <input type="tel" name="emergency_contact_phone" value="{{ old('emergency_contact_phone') }}" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">{{ __('Notes') }}</label>
                        <textarea name="notes" rows="2" class="form-control">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">{{ __('Add Tenant') }}</button>
                    <a href="{{ route('landlord-portal.tenants.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection
