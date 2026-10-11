@php($building = $building ?? null)

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Building Name</label>
        <input type="text" name="name" value="{{ old('name', $building?->name) }}" class="form-control @error('name') is-invalid @enderror" required>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Floor Count</label>
        <input type="number" min="0" name="floor_count" value="{{ old('floor_count', $building?->floor_count) }}" class="form-control @error('floor_count') is-invalid @enderror">
        @error('floor_count') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    @if ($building)
        <div class="col-md-3">
            <label class="form-label">{{ __('Status') }}</label>
            <select name="status" class="form-select @error('status') is-invalid @enderror">
                @foreach (['active', 'inactive', 'under_maintenance'] as $status)
                    <option value="{{ $status }}" @selected(old('status', $building->status) === $status)>{{ __(ucfirst(str_replace('_', ' ', $status))) }}</option>
                @endforeach
            </select>
            @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    @endif

    <div class="col-12">
        <label class="form-label">{{ __('Description') }}</label>
        <textarea name="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description', $building?->description) }}</textarea>
        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>
