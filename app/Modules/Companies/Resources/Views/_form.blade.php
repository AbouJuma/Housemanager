@php($company = $company ?? null)

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Company Name</label>
        <input type="text" name="name" value="{{ old('name', $company?->name) }}" class="form-control @error('name') is-invalid @enderror" required>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Slug <span class="text-secondary fw-normal">(auto-generated if left blank)</span></label>
        <input type="text" name="slug" value="{{ old('slug', $company?->slug) }}" class="form-control @error('slug') is-invalid @enderror">
        @error('slug') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Email</label>
        <input type="email" name="email" value="{{ old('email', $company?->email) }}" class="form-control @error('email') is-invalid @enderror">
        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Phone</label>
        <input type="text" name="phone" value="{{ old('phone', $company?->phone) }}" class="form-control @error('phone') is-invalid @enderror">
        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Website</label>
        <input type="url" name="website" value="{{ old('website', $company?->website) }}" class="form-control @error('website') is-invalid @enderror" placeholder="https://">
        @error('website') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Country Code</label>
        <input type="text" name="country_code" maxlength="2" value="{{ old('country_code', $company?->country_code) }}" class="form-control text-uppercase @error('country_code') is-invalid @enderror" placeholder="e.g. TZ">
        @error('country_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Address Line 1</label>
        <input type="text" name="address_line1" value="{{ old('address_line1', $company?->address_line1) }}" class="form-control @error('address_line1') is-invalid @enderror">
        @error('address_line1') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Address Line 2</label>
        <input type="text" name="address_line2" value="{{ old('address_line2', $company?->address_line2) }}" class="form-control @error('address_line2') is-invalid @enderror">
        @error('address_line2') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">City</label>
        <input type="text" name="city" value="{{ old('city', $company?->city) }}" class="form-control @error('city') is-invalid @enderror">
        @error('city') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">State / Region</label>
        <input type="text" name="state" value="{{ old('state', $company?->state) }}" class="form-control @error('state') is-invalid @enderror">
        @error('state') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Postal Code</label>
        <input type="text" name="postal_code" value="{{ old('postal_code', $company?->postal_code) }}" class="form-control @error('postal_code') is-invalid @enderror">
        @error('postal_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">Timezone</label>
        <input type="text" name="timezone" value="{{ old('timezone', $company?->timezone ?? 'UTC') }}" class="form-control @error('timezone') is-invalid @enderror">
        @error('timezone') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Locale</label>
        <input type="text" name="locale" value="{{ old('locale', $company?->locale ?? 'en') }}" class="form-control @error('locale') is-invalid @enderror">
        @error('locale') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Currency Code</label>
        <input type="text" name="currency_code" maxlength="3" value="{{ old('currency_code', $company?->currency_code ?? 'USD') }}" class="form-control text-uppercase @error('currency_code') is-invalid @enderror">
        @error('currency_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    @if ($company)
        <div class="col-md-6">
            <label class="form-label">Status</label>
            <select name="status" class="form-select @error('status') is-invalid @enderror">
                @foreach (['trial', 'active', 'suspended', 'cancelled'] as $status)
                    <option value="{{ $status }}" @selected(old('status', $company->status) === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    @endif
</div>
