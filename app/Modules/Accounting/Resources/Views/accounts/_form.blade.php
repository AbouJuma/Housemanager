@php($account = $account ?? null)

<div class="row g-3">
    <div class="col-md-3">
        <label class="form-label">{{ __('Code') }}</label>
        <input type="text" name="code" value="{{ old('code', $account?->code) }}" class="form-control @error('code') is-invalid @enderror" required>
        @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-5">
        <label class="form-label">{{ __('Name') }}</label>
        <input type="text" name="name" value="{{ old('name', $account?->name) }}" class="form-control @error('name') is-invalid @enderror" required>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __('Type') }}</label>
        <select name="type" class="form-select @error('type') is-invalid @enderror" required>
            @foreach (['asset', 'liability', 'equity', 'income', 'expense'] as $type)
                <option value="{{ $type }}" @selected(old('type', $account?->type) === $type)>{{ ucfirst($type) }}</option>
            @endforeach
        </select>
        @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    @if ($account)
        <div class="col-md-4">
            <div class="form-check form-switch mt-4">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" class="form-check-input" id="isActiveSwitch" @checked(old('is_active', $account->is_active))>
                <label class="form-check-label" for="isActiveSwitch">{{ __('Active') }}</label>
            </div>
        </div>
    @endif

    <div class="col-12">
        <label class="form-label">{{ __('Description') }} <span class="text-secondary fw-normal">{{ __('(optional)') }}</span></label>
        <textarea name="description" rows="2" class="form-control @error('description') is-invalid @enderror">{{ old('description', $account?->description) }}</textarea>
        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>
