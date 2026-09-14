@php($item = $item ?? null)
@php($categories = ['appliance', 'furniture', 'tool', 'plumbing_supply', 'electrical_supply', 'cleaning_supply', 'hardware', 'safety_equipment', 'other'])
@php($units = ['piece', 'box', 'liter', 'kilogram', 'meter', 'roll', 'pack', 'other'])

<div class="row g-3">
    <div class="col-12">
        <label class="form-label">Name</label>
        <input type="text" name="name" value="{{ old('name', $item?->name) }}" class="form-control @error('name') is-invalid @enderror" required>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">SKU <span class="text-secondary fw-normal">(optional)</span></label>
        <input type="text" name="sku" value="{{ old('sku', $item?->sku) }}" class="form-control @error('sku') is-invalid @enderror">
        @error('sku') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Category</label>
        <select name="category" class="form-select @error('category') is-invalid @enderror" required>
            @foreach ($categories as $category)
                <option value="{{ $category }}" @selected(old('category', $item?->category ?? 'other') === $category)>{{ ucfirst(str_replace('_', ' ', $category)) }}</option>
            @endforeach
        </select>
        @error('category') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Unit of Measure</label>
        <select name="unit_of_measure" class="form-select @error('unit_of_measure') is-invalid @enderror" required>
            @foreach ($units as $unit)
                <option value="{{ $unit }}" @selected(old('unit_of_measure', $item?->unit_of_measure ?? 'piece') === $unit)>{{ ucfirst($unit) }}</option>
            @endforeach
        </select>
        @error('unit_of_measure') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">Property <span class="text-secondary fw-normal">(optional)</span></label>
        <select name="property_id" class="form-select @error('property_id') is-invalid @enderror">
            <option value="">&mdash; Company-wide &mdash;</option>
            @foreach ($properties as $property)
                <option value="{{ $property->id }}" @selected((int) old('property_id', $item?->property_id) === $property->id || (! old('property_id') && ! $item && ($selectedPropertyUuid ?? null) === $property->uuid))>{{ $property->name }}</option>
            @endforeach
        </select>
        @error('property_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Preferred Vendor <span class="text-secondary fw-normal">(optional)</span></label>
        <select name="vendor_id" class="form-select @error('vendor_id') is-invalid @enderror">
            <option value="">&mdash; None &mdash;</option>
            @foreach ($vendors as $vendor)
                <option value="{{ $vendor->id }}" @selected((int) old('vendor_id', $item?->vendor_id) === $vendor->id)>{{ $vendor->name }}</option>
            @endforeach
        </select>
        @error('vendor_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    @if ($item)
        <div class="col-md-4">
            <label class="form-label">Status</label>
            <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                @foreach (['active', 'discontinued'] as $status)
                    <option value="{{ $status }}" @selected(old('status', $item->status) === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    @endif

    <div class="col-md-6">
        <label class="form-label">Reorder Level <span class="text-secondary fw-normal">(optional)</span></label>
        <input type="number" step="0.01" min="0" name="reorder_level" value="{{ old('reorder_level', $item?->reorder_level) }}" class="form-control @error('reorder_level') is-invalid @enderror">
        <div class="form-text">You'll be flagged as low stock once quantity on hand drops to this level or below.</div>
        @error('reorder_level') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Unit Cost <span class="text-secondary fw-normal">(optional)</span></label>
        <input type="number" step="0.01" min="0" name="unit_cost" value="{{ old('unit_cost', $item?->unit_cost) }}" class="form-control @error('unit_cost') is-invalid @enderror">
        @error('unit_cost') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label class="form-label">Notes <span class="text-secondary fw-normal">(optional)</span></label>
        <textarea name="notes" rows="2" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $item?->notes) }}</textarea>
        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    @unless ($item)
        <div class="col-12">
            <div class="alert alert-info small mb-0">
                <i class="bi bi-info-circle me-1"></i> New items start at zero quantity on hand. Record a "Receipt" transaction from the item's page once it's saved to bring stock in.
            </div>
        </div>
    @endunless
</div>
