@extends('layouts.app')

@section('title', 'Edit Sale #' . $sale->id)

@section('page-heading')
    <h1 class="h4 mb-0">Edit Sale #{{ $sale->id }}</h1>
@endsection

@section('content')
    <x-breadcrumb :items="collect([
        ['label' => 'Sales', 'url' => route('sales.index')],
        ['label' => 'Sale #' . $sale->id, 'url' => route('sales.show', $sale)],
        ['label' => 'Edit'],
    ])" />

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('sales.update', $sale) }}">
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Unit') }}</label>
                        <select name="unit_id" class="form-select @error('unit_id') is-invalid @enderror">
                            <option value="">&mdash; Select a unit &mdash;</option>
                            @foreach ($units as $unit)
                                <option value="{{ $unit->id }}" @selected(old('unit_id', $sale->unit_id) === $unit->id)>{{ $unit->unit_number }} · {{ $unit->building->property->name }} · {{ $unit->building->name }}</option>
                            @endforeach
                        </select>
                        @error('unit_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Buyer') }}</label>
                        <select name="new_buyer_id" id="buyerSelect" class="form-select @error('new_buyer_id') is-invalid @enderror">
                            <option value="">{{ __('Select a buyer (optional)') }}</option>
                            @foreach ($buyers as $buyer)
                                <option value="{{ $buyer->id }}" @selected(old('new_buyer_id', $sale->new_buyer_id) === $buyer->id)>{{ $buyer->name }} ({{ $buyer->phone }})</option>
                            @endforeach
                        </select>
                        @error('new_buyer_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Sale Date') }}</label>
                        <input type="date" name="sale_date" value="{{ old('sale_date', $sale->sale_date->toDateString()) }}" class="form-control @error('sale_date') is-invalid @enderror">
                        @error('sale_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Total Price') }}</label>
                        <input type="number" step="0.01" min="0" name="total_price" value="{{ old('total_price', $sale->total_price) }}" class="form-control @error('total_price') is-invalid @enderror">
                        @error('total_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Down Payment') }}</label>
                        <input type="number" step="0.01" min="0" name="down_payment" value="{{ old('down_payment', $sale->down_payment) }}" class="form-control @error('down_payment') is-invalid @enderror">
                        @error('down_payment') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Installment Period (Months)') }}</label>
                        <input type="number" min="1" max="360" name="installment_period" value="{{ old('installment_period', $sale->installment_period) }}" class="form-control @error('installment_period') is-invalid @enderror">
                        @error('installment_period') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Installment Amount') }}</label>
                        <input type="number" step="0.01" min="0" name="installment_amount" value="{{ old('installment_amount', $sale->installment_amount) }}" class="form-control @error('installment_amount') is-invalid @enderror">
                        @error('installment_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Status') }}</label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror">
                            <option value="pending" @selected(old('status', $sale->status) === 'pending')">{{ __('Pending') }}</option>
                            <option value="active" @selected(old('status', $sale->status) === 'active')">{{ __('Active') }}</option>
                            <option value="completed" @selected(old('status', $sale->status) === 'completed')">{{ __('Completed') }}</option>
                            <option value="cancelled" @selected(old('status', $sale->status) === 'cancelled')">{{ __('Cancelled') }}</option>
                        </select>
                        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">{{ __('Notes') }}</label>
                        <textarea name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $sale->notes) }}</textarea>
                        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Update Sale</button>
                    <a href="{{ route('sales.show', $sale) }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Select2 for buyer dropdown (basic version)
            $('#buyerSelect').select2({
                placeholder: 'Select a buyer (optional)',
                allowClear: true,
                width: '100%'
            });
        });
    </script>
    @endpush
@endsection
