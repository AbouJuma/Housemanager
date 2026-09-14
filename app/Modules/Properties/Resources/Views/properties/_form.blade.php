@php($property = $property ?? null)

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Property Name</label>
        <input type="text" name="name" value="{{ old('name', $property?->name) }}" class="form-control @error('name') is-invalid @enderror" required>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Type</label>
        <select name="type" class="form-select @error('type') is-invalid @enderror" required>
            <option value="">Choose a type…</option>
            @foreach (['residential', 'commercial', 'mixed_use', 'warehouse', 'retail', 'hotel', 'hostel', 'industrial', 'land', 'other'] as $type)
                <option value="{{ $type }}" @selected(old('type', $property?->type) === $type)>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
            @endforeach
        </select>
        @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Landlord <span class="text-secondary fw-normal">(optional)</span></label>
        <select name="landlord_id" class="form-select @error('landlord_id') is-invalid @enderror">
            <option value="">&mdash; None &mdash;</option>
            @foreach ($landlords as $landlord)
                <option value="{{ $landlord->id }}" @selected((int) old('landlord_id', $property?->landlord_id) === $landlord->id)>{{ $landlord->name }}</option>
            @endforeach
        </select>
        @error('landlord_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    @if ($property)
        <div class="col-md-6">
            <label class="form-label">Status</label>
            <select name="status" class="form-select @error('status') is-invalid @enderror">
                @foreach (['active', 'inactive', 'under_maintenance', 'archived'] as $status)
                    <option value="{{ $status }}" @selected(old('status', $property->status) === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                @endforeach
            </select>
            @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    @endif

    <div class="col-12">
        <label class="form-label">Description</label>
        <textarea name="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description', $property?->description) }}</textarea>
        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Address Line 1</label>
        <input type="text" name="address_line1" value="{{ old('address_line1', $property?->address_line1) }}" class="form-control @error('address_line1') is-invalid @enderror">
        @error('address_line1') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Address Line 2</label>
        <input type="text" name="address_line2" value="{{ old('address_line2', $property?->address_line2) }}" class="form-control @error('address_line2') is-invalid @enderror">
        @error('address_line2') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">City</label>
        <input type="text" name="city" value="{{ old('city', $property?->city) }}" class="form-control @error('city') is-invalid @enderror">
        @error('city') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">State / Region</label>
        <input type="text" name="state" value="{{ old('state', $property?->state) }}" class="form-control @error('state') is-invalid @enderror">
        @error('state') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Postal Code</label>
        <input type="text" name="postal_code" value="{{ old('postal_code', $property?->postal_code) }}" class="form-control @error('postal_code') is-invalid @enderror">
        @error('postal_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">Country Code</label>
        <input type="text" name="country_code" maxlength="2" value="{{ old('country_code', $property?->country_code) }}" class="form-control text-uppercase @error('country_code') is-invalid @enderror" placeholder="e.g. TZ">
        @error('country_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Total Area</label>
        <input type="number" step="0.01" name="total_area" value="{{ old('total_area', $property?->total_area) }}" class="form-control @error('total_area') is-invalid @enderror">
        @error('total_area') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Area Unit</label>
        <select name="area_unit" class="form-select @error('area_unit') is-invalid @enderror">
            @foreach (['sqm', 'sqft'] as $unit)
                <option value="{{ $unit }}" @selected(old('area_unit', $property?->area_unit ?? 'sqm') === $unit)>{{ strtoupper($unit) }}</option>
            @endforeach
        </select>
        @error('area_unit') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Latitude <span class="text-secondary fw-normal">(optional)</span></label>
        <input type="number" step="0.0000001" name="latitude" value="{{ old('latitude', $property?->latitude) }}" class="form-control @error('latitude') is-invalid @enderror">
        @error('latitude') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Longitude <span class="text-secondary fw-normal">(optional)</span></label>
        <input type="number" step="0.0000001" name="longitude" value="{{ old('longitude', $property?->longitude) }}" class="form-control @error('longitude') is-invalid @enderror">
        @error('longitude') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>
