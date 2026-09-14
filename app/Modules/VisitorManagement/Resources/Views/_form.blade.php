@php($visitor = $visitor ?? null)
@php($purposes = ['delivery', 'meeting', 'maintenance', 'interview', 'personal', 'other'])

<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">Property</label>
        <select name="property_id" class="form-select @error('property_id') is-invalid @enderror" required>
            <option value="">&mdash; Select a property &mdash;</option>
            @foreach ($properties as $property)
                <option value="{{ $property->id }}" @selected((int) old('property_id', $visitor?->property_id) === $property->id || (! old('property_id') && ! $visitor && ($selectedPropertyUuid ?? null) === $property->uuid))>{{ $property->name }}</option>
            @endforeach
        </select>
        @error('property_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Unit <span class="text-secondary fw-normal">(optional)</span></label>
        <select name="unit_id" class="form-select @error('unit_id') is-invalid @enderror">
            <option value="">&mdash; Not unit-specific &mdash;</option>
            @foreach ($units as $unit)
                <option value="{{ $unit->id }}" @selected((int) old('unit_id', $visitor?->unit_id) === $unit->id)>{{ $unit->unit_number }} &middot; {{ $unit->building->property->name }}</option>
            @endforeach
        </select>
        @error('unit_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Visiting Tenant <span class="text-secondary fw-normal">(optional)</span></label>
        <select name="tenant_id" class="form-select @error('tenant_id') is-invalid @enderror">
            <option value="">&mdash; Not visiting a tenant &mdash;</option>
            @foreach ($tenants as $tenant)
                <option value="{{ $tenant->id }}" @selected((int) old('tenant_id', $visitor?->tenant_id) === $tenant->id)>{{ $tenant->name }}</option>
            @endforeach
        </select>
        @error('tenant_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Visitor Name</label>
        <input type="text" name="visitor_name" value="{{ old('visitor_name', $visitor?->visitor_name) }}" class="form-control @error('visitor_name') is-invalid @enderror" required>
        @error('visitor_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Visitor Phone <span class="text-secondary fw-normal">(optional)</span></label>
        <input type="text" name="visitor_phone" value="{{ old('visitor_phone', $visitor?->visitor_phone) }}" class="form-control @error('visitor_phone') is-invalid @enderror">
        @error('visitor_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Host <span class="text-secondary fw-normal">(optional, if not visiting a tenant)</span></label>
        <input type="text" name="host_name" value="{{ old('host_name', $visitor?->host_name) }}" class="form-control @error('host_name') is-invalid @enderror" placeholder="e.g. Building Management">
        @error('host_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Represents Vendor <span class="text-secondary fw-normal">(optional)</span></label>
        <select name="vendor_id" class="form-select @error('vendor_id') is-invalid @enderror">
            <option value="">&mdash; None &mdash;</option>
            @foreach ($vendors as $vendor)
                <option value="{{ $vendor->id }}" @selected((int) old('vendor_id', $visitor?->vendor_id) === $vendor->id)>{{ $vendor->name }}</option>
            @endforeach
        </select>
        @error('vendor_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label">Purpose</label>
        <select name="purpose" class="form-select @error('purpose') is-invalid @enderror" required>
            @foreach ($purposes as $purpose)
                <option value="{{ $purpose }}" @selected(old('purpose', $visitor?->purpose ?? 'other') === $purpose)>{{ ucfirst($purpose) }}</option>
            @endforeach
        </select>
        @error('purpose') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Vehicle Plate <span class="text-secondary fw-normal">(optional)</span></label>
        <input type="text" name="vehicle_plate" value="{{ old('vehicle_plate', $visitor?->vehicle_plate) }}" class="form-control @error('vehicle_plate') is-invalid @enderror">
        @error('vehicle_plate') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Badge Number <span class="text-secondary fw-normal">(optional)</span></label>
        <input type="text" name="badge_number" value="{{ old('badge_number', $visitor?->badge_number) }}" class="form-control @error('badge_number') is-invalid @enderror">
        @error('badge_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Check-In Time{{ $visitor ? '' : ' (optional, defaults to now)' }}</label>
        <input type="datetime-local" name="check_in_at" value="{{ old('check_in_at', $visitor?->check_in_at?->format('Y-m-d\TH:i')) }}" class="form-control @error('check_in_at') is-invalid @enderror" @required($visitor)>
        @error('check_in_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label class="form-label">Notes <span class="text-secondary fw-normal">(optional)</span></label>
        <textarea name="notes" rows="2" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $visitor?->notes) }}</textarea>
        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>
