@php($unit = $unit ?? null)

<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">Unit Number</label>
        <input type="text" name="unit_number" value="{{ old('unit_number', $unit?->unit_number) }}" class="form-control @error('unit_number') is-invalid @enderror" required>
        @error('unit_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Type</label>
        <select name="type" class="form-select @error('type') is-invalid @enderror" required>
            <option value="">Choose a type…</option>
            @foreach (['apartment', 'studio', 'office', 'shop', 'warehouse_space', 'room', 'other'] as $type)
                <option value="{{ $type }}" @selected(old('type', $unit?->type) === $type)>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
            @endforeach
        </select>
        @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Status</label>
        <select name="status" class="form-select @error('status') is-invalid @enderror">
            @foreach (['vacant', 'occupied', 'under_maintenance', 'reserved'] as $status)
                <option value="{{ $status }}" @selected(old('status', $unit?->status ?? 'vacant') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
            @endforeach
        </select>
        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label">Bedrooms</label>
        <input type="number" min="0" name="bedrooms" value="{{ old('bedrooms', $unit?->bedrooms) }}" class="form-control @error('bedrooms') is-invalid @enderror">
        @error('bedrooms') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Bathrooms</label>
        <input type="number" min="0" name="bathrooms" value="{{ old('bathrooms', $unit?->bathrooms) }}" class="form-control @error('bathrooms') is-invalid @enderror">
        @error('bathrooms') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Area</label>
        <input type="number" step="0.01" min="0" name="area" value="{{ old('area', $unit?->area) }}" class="form-control @error('area') is-invalid @enderror">
        @error('area') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Area Unit</label>
        <select name="area_unit" class="form-select @error('area_unit') is-invalid @enderror">
            @foreach (['sqm', 'sqft'] as $areaUnit)
                <option value="{{ $areaUnit }}" @selected(old('area_unit', $unit?->area_unit ?? 'sqm') === $areaUnit)>{{ strtoupper($areaUnit) }}</option>
            @endforeach
        </select>
        @error('area_unit') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">Rent Amount <span class="text-secondary fw-normal">(optional)</span></label>
        <input type="number" step="0.01" min="0" name="rent_amount" value="{{ old('rent_amount', $unit?->rent_amount) }}" class="form-control @error('rent_amount') is-invalid @enderror">
        @error('rent_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label class="form-label">Description</label>
        <textarea name="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description', $unit?->description) }}</textarea>
        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>
