@php($vendor = $vendor ?? null)
@php($categories = ['plumbing', 'electrical', 'hvac', 'cleaning', 'landscaping', 'pest_control', 'general_contractor', 'utility_provider', 'security', 'other'])

<div class="row g-3">
    <div class="col-md-5">
        <label class="form-label">Name</label>
        <input type="text" name="name" value="{{ old('name', $vendor?->name) }}" class="form-control @error('name') is-invalid @enderror" required>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Type</label>
        <select name="type" class="form-select @error('type') is-invalid @enderror" required>
            @foreach (['individual', 'company'] as $type)
                <option value="{{ $type }}" @selected(old('type', $vendor?->type ?? 'company') === $type)>{{ ucfirst($type) }}</option>
            @endforeach
        </select>
        @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Category</label>
        <select name="category" class="form-select @error('category') is-invalid @enderror" required>
            @foreach ($categories as $category)
                <option value="{{ $category }}" @selected(old('category', $vendor?->category) === $category)>{{ ucfirst(str_replace('_', ' ', $category)) }}</option>
            @endforeach
        </select>
        @error('category') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    @if ($vendor)
        <div class="col-md-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select @error('status') is-invalid @enderror">
                @foreach (['active', 'inactive'] as $status)
                    <option value="{{ $status }}" @selected(old('status', $vendor->status) === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    @endif

    <div class="col-md-4">
        <label class="form-label">Email</label>
        <input type="email" name="email" value="{{ old('email', $vendor?->email) }}" class="form-control @error('email') is-invalid @enderror">
        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Phone</label>
        <input type="text" name="phone" value="{{ old('phone', $vendor?->phone) }}" class="form-control @error('phone') is-invalid @enderror">
        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Secondary Phone</label>
        <input type="text" name="secondary_phone" value="{{ old('secondary_phone', $vendor?->secondary_phone) }}" class="form-control @error('secondary_phone') is-invalid @enderror">
        @error('secondary_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Address Line 1</label>
        <input type="text" name="address_line1" value="{{ old('address_line1', $vendor?->address_line1) }}" class="form-control @error('address_line1') is-invalid @enderror">
        @error('address_line1') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Address Line 2</label>
        <input type="text" name="address_line2" value="{{ old('address_line2', $vendor?->address_line2) }}" class="form-control @error('address_line2') is-invalid @enderror">
        @error('address_line2') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">City</label>
        <input type="text" name="city" value="{{ old('city', $vendor?->city) }}" class="form-control @error('city') is-invalid @enderror">
        @error('city') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">State / Region</label>
        <input type="text" name="state" value="{{ old('state', $vendor?->state) }}" class="form-control @error('state') is-invalid @enderror">
        @error('state') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Postal Code</label>
        <input type="text" name="postal_code" value="{{ old('postal_code', $vendor?->postal_code) }}" class="form-control @error('postal_code') is-invalid @enderror">
        @error('postal_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label">Country Code</label>
        <input type="text" name="country_code" maxlength="2" value="{{ old('country_code', $vendor?->country_code) }}" class="form-control text-uppercase @error('country_code') is-invalid @enderror" placeholder="e.g. TZ">
        @error('country_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-5">
        <label class="form-label">Tax ID</label>
        <input type="text" name="tax_id" value="{{ old('tax_id', $vendor?->tax_id) }}" class="form-control @error('tax_id') is-invalid @enderror">
        @error('tax_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <hr>
        <h2 class="h6 mb-3">Bank Details <span class="text-secondary fw-normal">(for paying this vendor, optional)</span></h2>
    </div>

    <div class="col-md-4">
        <label class="form-label">Bank Name</label>
        <input type="text" name="bank_name" value="{{ old('bank_name', $vendor?->bankDetail('name')) }}" class="form-control @error('bank_name') is-invalid @enderror">
        @error('bank_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Account Name</label>
        <input type="text" name="bank_account_name" value="{{ old('bank_account_name', $vendor?->bankDetail('account_name')) }}" class="form-control @error('bank_account_name') is-invalid @enderror">
        @error('bank_account_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Account Number</label>
        <input type="text" name="bank_account_number" value="{{ old('bank_account_number', $vendor?->bankDetail('account_number')) }}" class="form-control @error('bank_account_number') is-invalid @enderror">
        @error('bank_account_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Branch</label>
        <input type="text" name="bank_branch" value="{{ old('bank_branch', $vendor?->bankDetail('branch')) }}" class="form-control @error('bank_branch') is-invalid @enderror">
        @error('bank_branch') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">SWIFT / Branch Code</label>
        <input type="text" name="bank_swift_code" value="{{ old('bank_swift_code', $vendor?->bankDetail('swift_code')) }}" class="form-control @error('bank_swift_code') is-invalid @enderror">
        @error('bank_swift_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label class="form-label">Notes</label>
        <textarea name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $vendor?->notes) }}</textarea>
        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>
