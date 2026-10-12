@extends('landlord-portal::layout')

@section('title', $expense ? __('Edit Expense') : __('Add Expense'))

@section('content')
    <h1 class="h4 mb-4">{{ $expense ? __('Edit Expense') : __('Add Expense') }}</h1>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ $expense ? route('landlord-portal.expenses.update', $expense) : route('landlord-portal.expenses.store') }}">
                @csrf
                @if ($expense)
                    @method('PUT')
                @endif

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Property') }}</label>
                        <select name="property_id" class="form-select @error('property_id') is-invalid @enderror" required>
                            <option value="">{{ __('Choose…') }}</option>
                            @foreach ($properties as $property)
                                <option value="{{ $property->id }}" @selected((int) old('property_id', $expense?->property_id) === $property->id)>{{ $property->name }}</option>
                            @endforeach
                        </select>
                        @error('property_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Description') }}</label>
                        <input type="text" name="description" value="{{ old('description', $expense?->description) }}" class="form-control @error('description') is-invalid @enderror" placeholder="{{ __('e.g. Plumbing repair') }}" required>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    @if ($expense)
                        <div class="col-12">
                            <div class="alert alert-light border small mb-0">
                                {{ __('Amount :amount on :date', ['amount' => number_format($expense->amount, 2), 'date' => $expense->expense_date->format('d M Y')]) }}
                                · {{ $expense->account?->name }} · {{ __(ucfirst($expense->payment_status)) }}
                                <div class="text-secondary">{{ __('Amount, date and payment are already in the books and can\'t be changed here - ask the office to void it if it is wrong.') }}</div>
                            </div>
                        </div>
                    @else
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Amount') }}</label>
                            <input type="number" step="0.01" min="0" name="amount" value="{{ old('amount') }}" class="form-control @error('amount') is-invalid @enderror" required>
                            @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Date') }}</label>
                            <input type="date" name="expense_date" value="{{ old('expense_date', company_now()->toDateString()) }}" max="{{ company_now()->toDateString() }}" class="form-control @error('expense_date') is-invalid @enderror" required>
                            @error('expense_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Category') }}</label>
                            <select name="account_id" class="form-select @error('account_id') is-invalid @enderror" required>
                                <option value="">{{ __('Choose…') }}</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected((int) old('account_id') === $category->id)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                            @error('account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Payment') }}</label>
                            <select name="payment_status" id="paymentStatus" class="form-select @error('payment_status') is-invalid @enderror">
                                <option value="unpaid" @selected(old('payment_status', 'unpaid') === 'unpaid')>{{ __('Not paid yet') }}</option>
                                <option value="paid" @selected(old('payment_status') === 'paid')>{{ __('Already paid') }}</option>
                            </select>
                        </div>
                        <div class="col-md-4 js-paid">
                            <label class="form-label">{{ __('Paid with') }}</label>
                            <select name="payment_method" class="form-select @error('payment_method') is-invalid @enderror">
                                <option value="">—</option>
                                @foreach (\App\Modules\LandlordPortal\Http\Requests\LandlordExpenseRequest::METHODS as $method)
                                    <option value="{{ $method }}" @selected(old('payment_method') === $method)>{{ __(ucfirst(str_replace('_', ' ', $method))) }}</option>
                                @endforeach
                            </select>
                            @error('payment_method') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4 js-paid">
                            <label class="form-label">{{ __('Paid on') }}</label>
                            <input type="date" name="paid_on" value="{{ old('paid_on', company_now()->toDateString()) }}" class="form-control @error('paid_on') is-invalid @enderror">
                            @error('paid_on') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    @endif

                    <div class="col-md-6">
                        <label class="form-label">{{ __('Paid to') }} <span class="text-secondary fw-normal">{{ __('(optional)') }}</span></label>
                        <input type="text" name="vendor_name" value="{{ old('vendor_name', $expense?->vendor_name) }}" class="form-control" placeholder="{{ __('Supplier or person') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Reference') }} <span class="text-secondary fw-normal">{{ __('(optional)') }}</span></label>
                        <input type="text" name="reference" value="{{ old('reference', $expense?->reference) }}" class="form-control" placeholder="{{ __('Receipt number') }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">{{ __('Notes') }}</label>
                        <textarea name="notes" rows="2" class="form-control">{{ old('notes', $expense?->notes) }}</textarea>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">{{ $expense ? __('Save Changes') : __('Add Expense') }}</button>
                    <a href="{{ route('landlord-portal.expenses.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const status = document.getElementById('paymentStatus');
            if (!status) return;
            const toggle = () => document.querySelectorAll('.js-paid').forEach((el) => el.classList.toggle('d-none', status.value !== 'paid'));
            status.addEventListener('change', toggle);
            toggle();
        });
    </script>
@endpush
