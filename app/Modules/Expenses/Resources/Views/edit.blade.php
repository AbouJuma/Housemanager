@extends('layouts.app')

@section('title', __('Edit Expense'))

@section('page-heading')
    <h1 class="h4 mb-0">{{ __('Edit Expense') }}</h1>
@endsection

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <p class="text-secondary small">Amount, category, date, and payment status can't be changed once an expense is recorded — void this expense and record a new one to correct those. Only the details below can be edited.</p>

            <form method="POST" action="{{ route('expenses.update', $expense) }}">
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Property') }} <span class="text-secondary fw-normal">{{ __('(optional)') }}</span></label>
                        <select name="property_id" class="form-select @error('property_id') is-invalid @enderror">
                            <option value="">&mdash; Not property-specific &mdash;</option>
                            @foreach ($properties as $property)
                                <option value="{{ $property->id }}" @selected((int) old('property_id', $expense->property_id) === $property->id)>{{ $property->name }}</option>
                            @endforeach
                        </select>
                        @error('property_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('Vendor') }} <span class="text-secondary fw-normal">{{ __('(optional)') }}</span></label>
                        <select name="vendor_id" class="form-select @error('vendor_id') is-invalid @enderror">
                            <option value="">&mdash; Not on file &mdash;</option>
                            @foreach ($vendors as $vendor)
                                <option value="{{ $vendor->id }}" @selected((int) old('vendor_id', $expense->vendor_id) === $vendor->id)>{{ $vendor->name }}</option>
                            @endforeach
                        </select>
                        @error('vendor_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('Vendor Name') }} <span class="text-secondary fw-normal">(if not on file above)</span></label>
                        <input type="text" name="vendor_name" value="{{ old('vendor_name', $expense->vendor_name) }}" class="form-control @error('vendor_name') is-invalid @enderror">
                        @error('vendor_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label">{{ __('Description') }}</label>
                        <input type="text" name="description" value="{{ old('description', $expense->description) }}" class="form-control @error('description') is-invalid @enderror" required>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('Reference') }} <span class="text-secondary fw-normal">{{ __('(optional)') }}</span></label>
                        <input type="text" name="reference" value="{{ old('reference', $expense->reference) }}" class="form-control @error('reference') is-invalid @enderror">
                        @error('reference') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">{{ __('Notes') }} <span class="text-secondary fw-normal">{{ __('(optional)') }}</span></label>
                        <textarea name="notes" rows="2" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $expense->notes) }}</textarea>
                        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">{{ __('Save Changes') }}</button>
                    <a href="{{ route('expenses.show', $expense) }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection
