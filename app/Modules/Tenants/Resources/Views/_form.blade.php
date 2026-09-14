@php($tenant = $tenant ?? null)

<script>
document.addEventListener('DOMContentLoaded', function() {
    const controlNumberInput = document.querySelector('input[name="control_number"]');
    if (controlNumberInput && !controlNumberInput.value) {
        // Generate random 10-digit number starting with 9
        const randomNum = '9' + Math.floor(Math.random() * 1000000000).toString().padStart(9, '0');
        controlNumberInput.value = randomNum;
        controlNumberInput.readOnly = true;
    }
});
</script>

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Name</label>
        <input type="text" name="name" value="{{ old('name', $tenant?->name) }}" class="form-control @error('name') is-invalid @enderror" required>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Type</label>
        <select name="type" class="form-select @error('type') is-invalid @enderror" required>
            @foreach (['individual', 'company'] as $type)
                <option value="{{ $type }}" @selected(old('type', $tenant?->type ?? 'individual') === $type)>{{ ucfirst($type) }}</option>
            @endforeach
        </select>
        @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    @if ($tenant)
        <div class="col-md-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select @error('status') is-invalid @enderror">
                @foreach (['active', 'inactive', 'blacklisted'] as $status)
                    <option value="{{ $status }}" @selected(old('status', $tenant->status) === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    @endif

    <div class="col-md-4">
        <label class="form-label">Email</label>
        <input type="email" name="email" value="{{ old('email', $tenant?->email) }}" class="form-control @error('email') is-invalid @enderror">
        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Phone</label>
        <input type="text" name="phone" value="{{ old('phone', $tenant?->phone) }}" class="form-control @error('phone') is-invalid @enderror">
        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Control Number</label>
        <input type="text" name="control_number" value="{{ old('control_number', $tenant?->control_number) }}" class="form-control @error('control_number') is-invalid @enderror" placeholder="Auto-generated if left blank">
        @error('control_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Secondary Phone</label>
        <input type="text" name="secondary_phone" value="{{ old('secondary_phone', $tenant?->secondary_phone) }}" class="form-control @error('secondary_phone') is-invalid @enderror">
        @error('secondary_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">Date of Birth</label>
        <input type="date" name="date_of_birth" value="{{ old('date_of_birth', $tenant?->date_of_birth?->toDateString()) }}" class="form-control @error('date_of_birth') is-invalid @enderror">
        @error('date_of_birth') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">ID Type</label>
        <select name="identification_type" class="form-select @error('identification_type') is-invalid @enderror">
            <option value="">&mdash; None &mdash;</option>
            @foreach (['national_id' => 'National ID', 'passport' => 'Passport', 'driving_license' => 'Driving License', 'voter_id' => 'Voter ID', 'other' => 'Other'] as $value => $label)
                <option value="{{ $value }}" @selected(old('identification_type', $tenant?->identification_type) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('identification_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">ID Number</label>
        <input type="text" name="identification_number" value="{{ old('identification_number', $tenant?->identification_number) }}" class="form-control @error('identification_number') is-invalid @enderror">
        @error('identification_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Address Line 1</label>
        <input type="text" name="address_line1" value="{{ old('address_line1', $tenant?->address_line1) }}" class="form-control @error('address_line1') is-invalid @enderror">
        @error('address_line1') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Address Line 2</label>
        <input type="text" name="address_line2" value="{{ old('address_line2', $tenant?->address_line2) }}" class="form-control @error('address_line2') is-invalid @enderror">
        @error('address_line2') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">City</label>
        <input type="text" name="city" value="{{ old('city', $tenant?->city) }}" class="form-control @error('city') is-invalid @enderror">
        @error('city') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">State / Region</label>
        <input type="text" name="state" value="{{ old('state', $tenant?->state) }}" class="form-control @error('state') is-invalid @enderror">
        @error('state') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Postal Code</label>
        <input type="text" name="postal_code" value="{{ old('postal_code', $tenant?->postal_code) }}" class="form-control @error('postal_code') is-invalid @enderror">
        @error('postal_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Country Code</label>
        <input type="text" name="country_code" maxlength="2" value="{{ old('country_code', $tenant?->country_code) }}" class="form-control text-uppercase @error('country_code') is-invalid @enderror" placeholder="e.g. TZ">
        @error('country_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <hr>
        <h2 class="h6 mb-3">Emergency Contact <span class="text-secondary fw-normal">(optional)</span></h2>
    </div>
    <div class="col-md-6">
        <label class="form-label">Contact Name</label>
        <input type="text" name="emergency_contact_name" value="{{ old('emergency_contact_name', $tenant?->emergency_contact_name) }}" class="form-control @error('emergency_contact_name') is-invalid @enderror">
        @error('emergency_contact_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Contact Phone</label>
        <input type="text" name="emergency_contact_phone" value="{{ old('emergency_contact_phone', $tenant?->emergency_contact_phone) }}" class="form-control @error('emergency_contact_phone') is-invalid @enderror">
        @error('emergency_contact_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label class="form-label">Notes</label>
        <textarea name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $tenant?->notes) }}</textarea>
        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>
