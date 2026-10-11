@php($landlord = $landlord ?? null)

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">{{ __('Name') }}</label>
        <input id="name" type="text" name="name" value="{{ old('name', $landlord?->name) }}" class="form-control @error('name') is-invalid @enderror" required>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
        <x-username-field type="landlord" :value="$landlord?->username" :ignore="$landlord?->id" />
    <div class="col-md-3">
        <label class="form-label">{{ __('Type') }}</label>
        <select name="type" class="form-select @error('type') is-invalid @enderror" required>
            @foreach (['individual', 'company'] as $type)
                <option value="{{ $type }}" @selected(old('type', $landlord?->type ?? 'individual') === $type)>{{ ucfirst($type) }}</option>
            @endforeach
        </select>
        @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    @if ($landlord)
        <div class="col-md-3">
            <label class="form-label">{{ __('Status') }}</label>
            <select name="status" class="form-select @error('status') is-invalid @enderror">
                @foreach (['active', 'inactive'] as $status)
                    <option value="{{ $status }}" @selected(old('status', $landlord->status) === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    @endif

    <div class="col-md-4">
        <label class="form-label">{{ __('Email') }}</label>
        <input type="email" name="email" value="{{ old('email', $landlord?->email) }}" class="form-control @error('email') is-invalid @enderror">
        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __('Phone') }} <span class="text-danger">*</span></label>
        <input type="text" name="phone" value="{{ old('phone', $landlord?->phone) }}" class="form-control @error('phone') is-invalid @enderror" required>
        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __('Secondary Phone') }}</label>
        <input type="text" name="secondary_phone" value="{{ old('secondary_phone', $landlord?->secondary_phone) }}" class="form-control @error('secondary_phone') is-invalid @enderror">
        @error('secondary_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">{{ __('Address Line 1') }}</label>
        <input type="text" name="address_line1" value="{{ old('address_line1', $landlord?->address_line1) }}" class="form-control @error('address_line1') is-invalid @enderror">
        @error('address_line1') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ __('Address Line 2') }}</label>
        <input type="text" name="address_line2" value="{{ old('address_line2', $landlord?->address_line2) }}" class="form-control @error('address_line2') is-invalid @enderror">
        @error('address_line2') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">{{ __('City') }}</label>
        <input type="text" name="city" value="{{ old('city', $landlord?->city) }}" class="form-control @error('city') is-invalid @enderror">
        @error('city') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __('State / Region') }}</label>
        <input type="text" name="state" value="{{ old('state', $landlord?->state) }}" class="form-control @error('state') is-invalid @enderror">
        @error('state') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __('Postal Code') }}</label>
        <input type="text" name="postal_code" value="{{ old('postal_code', $landlord?->postal_code) }}" class="form-control @error('postal_code') is-invalid @enderror">
        @error('postal_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label">{{ __('Country Code') }}</label>
        <input type="text" name="country_code" maxlength="2" value="{{ old('country_code', $landlord?->country_code) }}" class="form-control text-uppercase @error('country_code') is-invalid @enderror" placeholder="e.g. TZ">
        @error('country_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-5">
        <label class="form-label">{{ __('Tax ID') }}</label>
        <input type="text" name="tax_id" value="{{ old('tax_id', $landlord?->tax_id) }}" class="form-control @error('tax_id') is-invalid @enderror">
        @error('tax_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <hr>
        <h2 class="h6 mb-3">{{ __('Bank Details') }} <span class="text-secondary fw-normal">(for rent payouts, optional)</span></h2>
    </div>

    <div class="col-md-4">
        <label class="form-label">{{ __('Bank Name') }}</label>
        <input type="text" name="bank_name" value="{{ old('bank_name', $landlord?->bankDetail('name')) }}" class="form-control @error('bank_name') is-invalid @enderror">
        @error('bank_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __('Account Name') }}</label>
        <input type="text" name="bank_account_name" value="{{ old('bank_account_name', $landlord?->bankDetail('account_name')) }}" class="form-control @error('bank_account_name') is-invalid @enderror">
        @error('bank_account_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __('Account Number') }}</label>
        <input type="text" name="bank_account_number" value="{{ old('bank_account_number', $landlord?->bankDetail('account_number')) }}" class="form-control @error('bank_account_number') is-invalid @enderror">
        @error('bank_account_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ __('Branch') }}</label>
        <input type="text" name="bank_branch" value="{{ old('bank_branch', $landlord?->bankDetail('branch')) }}" class="form-control @error('bank_branch') is-invalid @enderror">
        @error('bank_branch') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ __('SWIFT / Branch Code') }}</label>
        <input type="text" name="bank_swift_code" value="{{ old('bank_swift_code', $landlord?->bankDetail('swift_code')) }}" class="form-control @error('bank_swift_code') is-invalid @enderror">
        @error('bank_swift_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label class="form-label">{{ __('Notes') }}</label>
        <textarea name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $landlord?->notes) }}</textarea>
        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>
