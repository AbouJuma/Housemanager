@php($maintenanceRequest = $maintenanceRequest ?? null)
@php($categories = ['plumbing', 'electrical', 'hvac', 'cleaning', 'landscaping', 'pest_control', 'general_contractor', 'utility_provider', 'security', 'other'])

<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">Property</label>
        <select name="property_id" class="form-select @error('property_id') is-invalid @enderror" required>
            <option value="">&mdash; Select a property &mdash;</option>
            @foreach ($properties as $property)
                <option value="{{ $property->id }}" @selected((int) old('property_id', $maintenanceRequest?->property_id) === $property->id || (! old('property_id') && ! $maintenanceRequest && ($selectedPropertyUuid ?? null) === $property->uuid))>{{ $property->name }}</option>
            @endforeach
        </select>
        @error('property_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Unit <span class="text-secondary fw-normal">(optional)</span></label>
        <select name="unit_id" class="form-select @error('unit_id') is-invalid @enderror">
            <option value="">&mdash; Not unit-specific &mdash;</option>
            @foreach ($units as $unit)
                <option value="{{ $unit->id }}" @selected((int) old('unit_id', $maintenanceRequest?->unit_id) === $unit->id)>{{ $unit->unit_number }} &middot; {{ $unit->building->property->name }}</option>
            @endforeach
        </select>
        @error('unit_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Reported By Tenant <span class="text-secondary fw-normal">(optional)</span></label>
        <select name="tenant_id" class="form-select @error('tenant_id') is-invalid @enderror">
            <option value="">&mdash; Not tenant-reported &mdash;</option>
            @foreach ($tenants as $tenant)
                <option value="{{ $tenant->id }}" @selected((int) old('tenant_id', $maintenanceRequest?->tenant_id) === $tenant->id)>{{ $tenant->name }}</option>
            @endforeach
        </select>
        @error('tenant_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label class="form-label">Title</label>
        <input type="text" name="title" value="{{ old('title', $maintenanceRequest?->title) }}" class="form-control @error('title') is-invalid @enderror" required>
        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-12">
        <label class="form-label">Description <span class="text-secondary fw-normal">(optional)</span></label>
        <textarea name="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description', $maintenanceRequest?->description) }}</textarea>
        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label">Category</label>
        <select name="category" class="form-select @error('category') is-invalid @enderror" required>
            @foreach ($categories as $category)
                <option value="{{ $category }}" @selected(old('category', $maintenanceRequest?->category ?? 'other') === $category)>{{ ucfirst(str_replace('_', ' ', $category)) }}</option>
            @endforeach
        </select>
        @error('category') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Priority</label>
        <select name="priority" class="form-select @error('priority') is-invalid @enderror" required>
            @foreach (['low', 'medium', 'high', 'urgent'] as $priority)
                <option value="{{ $priority }}" @selected(old('priority', $maintenanceRequest?->priority ?? 'medium') === $priority)>{{ ucfirst($priority) }}</option>
            @endforeach
        </select>
        @error('priority') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    @if ($maintenanceRequest)
        <div class="col-md-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                @foreach (['open', 'in_progress', 'completed', 'cancelled'] as $status)
                    <option value="{{ $status }}" @selected(old('status', $maintenanceRequest->status) === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                @endforeach
            </select>
            @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    @endif
    <div class="col-md-3">
        <label class="form-label">Assigned Vendor <span class="text-secondary fw-normal">(optional)</span></label>
        <select name="vendor_id" class="form-select @error('vendor_id') is-invalid @enderror">
            <option value="">&mdash; Unassigned &mdash;</option>
            @foreach ($vendors as $vendor)
                <option value="{{ $vendor->id }}" @selected((int) old('vendor_id', $maintenanceRequest?->vendor_id) === $vendor->id)>{{ $vendor->name }} ({{ ucfirst(str_replace('_', ' ', $vendor->category)) }})</option>
            @endforeach
        </select>
        @error('vendor_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">Reported Date</label>
        <input type="date" name="reported_date" value="{{ old('reported_date', $maintenanceRequest?->reported_date?->toDateString() ?? now()->toDateString()) }}" class="form-control @error('reported_date') is-invalid @enderror" required>
        @error('reported_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Scheduled Date <span class="text-secondary fw-normal">(optional)</span></label>
        <input type="date" name="scheduled_date" value="{{ old('scheduled_date', $maintenanceRequest?->scheduled_date?->toDateString()) }}" class="form-control @error('scheduled_date') is-invalid @enderror">
        @error('scheduled_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Estimated Cost <span class="text-secondary fw-normal">(optional)</span></label>
        <input type="number" step="0.01" min="0" name="estimated_cost" value="{{ old('estimated_cost', $maintenanceRequest?->estimated_cost) }}" class="form-control @error('estimated_cost') is-invalid @enderror">
        @error('estimated_cost') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label class="form-label">Notes <span class="text-secondary fw-normal">(optional)</span></label>
        <textarea name="notes" rows="2" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $maintenanceRequest?->notes) }}</textarea>
        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>
