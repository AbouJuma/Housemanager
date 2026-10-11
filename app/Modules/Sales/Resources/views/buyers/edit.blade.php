@extends('layouts.app')

@section('title', 'Edit Buyer - ' . $buyer->name)

@section('page-heading')
    <h1 class="h4 mb-0">Edit Buyer</h1>
@endsection

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('buyers.update', $buyer) }}">
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Name') }} <span class="text-danger">*</span></label>
                        <input id="name" type="text" name="name" value="{{ old('name', $buyer->name) }}" class="form-control @error('name') is-invalid @enderror" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                        <x-username-field type="buyer" :value="$buyer->username" :ignore="$buyer->id" />
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Email') }}</label>
                        <input type="email" name="email" value="{{ old('email', $buyer->email) }}" class="form-control @error('email') is-invalid @enderror">
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Phone') }} <span class="text-danger">*</span></label>
                        <input type="text" name="phone" value="{{ old('phone', $buyer->phone) }}" class="form-control @error('phone') is-invalid @enderror" required>
                        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('ID Number') }}</label>
                        <input type="text" name="id_number" value="{{ old('id_number', $buyer->id_number) }}" class="form-control @error('id_number') is-invalid @enderror">
                        @error('id_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">{{ __('Address') }}</label>
                        <textarea name="address" rows="2" class="form-control @error('address') is-invalid @enderror">{{ old('address', $buyer->address) }}</textarea>
                        @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Status') }}</label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror">
                            <option value="active" {{ old('status', $buyer->status) === 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                            <option value="inactive" {{ old('status', $buyer->status) === 'inactive' ? 'selected' : '' }}>{{ __('Inactive') }}</option>
                        </select>
                        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">{{ __('Notes') }}</label>
                        <textarea name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $buyer->notes) }}</textarea>
                        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Update Buyer</button>
                    <a href="{{ route('buyers.show', $buyer) }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection
