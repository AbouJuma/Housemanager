@php($plan = $plan ?? null)

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Plan Name</label>
        <input type="text" name="name" value="{{ old('name', $plan?->name) }}" class="form-control @error('name') is-invalid @enderror" required>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Slug <span class="text-secondary fw-normal">(auto-generated if left blank)</span></label>
        <input type="text" name="slug" value="{{ old('slug', $plan?->slug) }}" class="form-control @error('slug') is-invalid @enderror">
        @error('slug') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label class="form-label">{{ __('Description') }}</label>
        <textarea name="description" rows="2" class="form-control @error('description') is-invalid @enderror">{{ old('description', $plan?->description) }}</textarea>
        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">{{ __('Price') }}</label>
        <input type="number" step="0.01" min="0" name="price" value="{{ old('price', $plan?->price) }}" class="form-control @error('price') is-invalid @enderror" required>
        @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __('Billing Cycle') }}</label>
        <select name="billing_cycle" class="form-select @error('billing_cycle') is-invalid @enderror" required>
            @foreach (['monthly', 'yearly'] as $cycle)
                <option value="{{ $cycle }}" @selected(old('billing_cycle', $plan?->billing_cycle ?? 'monthly') === $cycle)>{{ ucfirst($cycle) }}</option>
            @endforeach
        </select>
        @error('billing_cycle') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __('Sort Order') }}</label>
        <input type="number" min="0" name="sort_order" value="{{ old('sort_order', $plan?->sort_order ?? 0) }}" class="form-control @error('sort_order') is-invalid @enderror">
        @error('sort_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">{{ __('Max Properties') }} <span class="text-secondary fw-normal">(blank = unlimited)</span></label>
        <input type="number" min="1" name="max_properties" value="{{ old('max_properties', $plan?->max_properties) }}" class="form-control @error('max_properties') is-invalid @enderror">
        @error('max_properties') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __('Max Units') }} <span class="text-secondary fw-normal">(blank = unlimited)</span></label>
        <input type="number" min="1" name="max_units" value="{{ old('max_units', $plan?->max_units) }}" class="form-control @error('max_units') is-invalid @enderror">
        @error('max_units') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __('Max Users') }} <span class="text-secondary fw-normal">(blank = unlimited)</span></label>
        <input type="number" min="1" name="max_users" value="{{ old('max_users', $plan?->max_users) }}" class="form-control @error('max_users') is-invalid @enderror">
        @error('max_users') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <div class="form-check form-switch">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" id="is_active" class="form-check-input" {{ old('is_active', $plan?->is_active ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active">{{ __('Active') }}</label>
        </div>
    </div>
</div>
