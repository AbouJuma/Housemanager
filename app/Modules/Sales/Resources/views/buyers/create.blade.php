@extends('layouts.app')

@section('title', __('New Buyer'))

@section('page-heading')
    <h1 class="h4 mb-0">{{ __('New Buyer') }}</h1>
@endsection

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('buyers.store') }}">
                @csrf
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
                        <label class="form-label">{{ __('Name') }} <span class="text-danger">*</span></label>
                        <input id="name" type="text" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                        <x-username-field type="buyer" :value="null" :ignore="null" />
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Email') }}</label>
                        <input type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror">
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Phone') }} <span class="text-danger">*</span></label>
                        <input type="text" name="phone" value="{{ old('phone') }}" class="form-control @error('phone') is-invalid @enderror" required>
                        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Control Number') }}</label>
                        <input type="text" name="control_number" value="{{ old('control_number') }}" class="form-control @error('control_number') is-invalid @enderror" placeholder="Auto-generated if left blank">
                        @error('control_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('ID Number') }}</label>
                        <input type="text" name="id_number" value="{{ old('id_number') }}" class="form-control @error('id_number') is-invalid @enderror">
                        @error('id_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">{{ __('Address') }}</label>
                        <textarea name="address" rows="2" class="form-control @error('address') is-invalid @enderror">{{ old('address') }}</textarea>
                        @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Status') }}</label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror">
                            <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                            <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>{{ __('Inactive') }}</option>
                        </select>
                        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">{{ __('Notes') }}</label>
                        <textarea name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea>
                        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Create Buyer</button>
                    <a href="{{ route('buyers.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection
