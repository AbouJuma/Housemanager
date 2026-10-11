@extends('layouts.app')

@section('title', __('New Expense'))

@section('page-heading')
    <h1 class="h4 mb-0">{{ __('New Expense') }}</h1>
@endsection

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('expenses.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Category') }}</label>
                        <select name="account_id" id="account-select" class="form-select @error('account_id') is-invalid @enderror" required>
                            <option value="">&mdash; Select an expense account &mdash;</option>
                            @foreach ($accounts as $account)
                                <option value="{{ $account->id }}" @selected((int) old('account_id') === $account->id)>{{ $account->code }} &middot; {{ $account->name }}</option>
                            @endforeach
                        </select>
                        <div class="mt-1">
                            <button type="button" class="btn btn-sm btn-link text-primary p-0" data-bs-toggle="modal" data-bs-target="#addAccountModal">
                                <i class="bi bi-plus-circle"></i> {{ __('Add new category') }}
                            </button>
                        </div>
                        @error('account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Property') }} <span class="text-secondary fw-normal">{{ __('(optional)') }}</span></label>
                        <select name="property_id" class="form-select @error('property_id') is-invalid @enderror">
                            <option value="">&mdash; Not property-specific &mdash;</option>
                            @foreach ($properties as $property)
                                <option value="{{ $property->id }}" @selected((int) old('property_id') === $property->id)>{{ $property->name }}</option>
                            @endforeach
                        </select>
                        @error('property_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label">{{ __('Description') }}</label>
                        <input type="text" name="description" value="{{ old('description') }}" class="form-control @error('description') is-invalid @enderror" required>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">{{ __('Vendor') }} <span class="text-secondary fw-normal">{{ __('(optional)') }}</span></label>
                        <select name="vendor_id" class="form-select @error('vendor_id') is-invalid @enderror">
                            <option value="">&mdash; Not on file &mdash;</option>
                            @foreach ($vendors as $vendor)
                                <option value="{{ $vendor->id }}" @selected((int) old('vendor_id') === $vendor->id)>{{ $vendor->name }}</option>
                            @endforeach
                        </select>
                        @error('vendor_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Vendor Name') }} <span class="text-secondary fw-normal">(if not on file above)</span></label>
                        <input type="text" name="vendor_name" value="{{ old('vendor_name') }}" class="form-control @error('vendor_name') is-invalid @enderror">
                        @error('vendor_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Amount') }}</label>
                        <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" class="form-control @error('amount') is-invalid @enderror" required>
                        @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Expense Date') }}</label>
                        <input type="date" name="expense_date" value="{{ old('expense_date', now()->toDateString()) }}" class="form-control @error('expense_date') is-invalid @enderror" required>
                        @error('expense_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12"><hr></div>

                    <div class="col-md-4">
                        <label class="form-label">{{ __('Payment Status') }}</label>
                        <select name="payment_status" id="paymentStatusSelect" class="form-select @error('payment_status') is-invalid @enderror" required>
                            <option value="unpaid" @selected(old('payment_status', 'unpaid') === 'unpaid')>{{ __('Unpaid (bill owed)') }}</option>
                            <option value="paid" @selected(old('payment_status') === 'paid')>{{ __('Paid now') }}</option>
                        </select>
                        @error('payment_status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4" id="paymentMethodField">
                        <label class="form-label">{{ __('Payment Method') }}</label>
                        <select name="payment_method" class="form-select @error('payment_method') is-invalid @enderror">
                            @foreach (['cash', 'bank_transfer', 'cheque', 'mobile_money', 'other'] as $method)
                                <option value="{{ $method }}" @selected(old('payment_method') === $method)>{{ ucfirst(str_replace('_', ' ', $method)) }}</option>
                            @endforeach
                        </select>
                        @error('payment_method') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4" id="paidOnField">
                        <label class="form-label">{{ __('Paid On') }}</label>
                        <input type="date" name="paid_on" value="{{ old('paid_on', now()->toDateString()) }}" class="form-control @error('paid_on') is-invalid @enderror">
                        @error('paid_on') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('Reference') }} <span class="text-secondary fw-normal">{{ __('(optional)') }}</span></label>
                        <input type="text" name="reference" value="{{ old('reference') }}" class="form-control @error('reference') is-invalid @enderror">
                        @error('reference') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">{{ __('Notes') }} <span class="text-secondary fw-normal">{{ __('(optional)') }}</span></label>
                        <textarea name="notes" rows="2" class="form-control @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea>
                        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">{{ __('Record Expense') }}</button>
                    <a href="{{ route('expenses.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Account Modal -->
    <div class="modal fade" id="addAccountModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Add New Expense Category') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addAccountForm">
                        @csrf
                        <input type="hidden" name="type" value="expense">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('Code') }} <span class="text-danger">*</span></label>
                                <input type="text" name="code" class="form-control" required placeholder="e.g. 6000">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('Name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required placeholder="e.g. Repairs">
                            </div>
                            <div class="col-12">
                                <label class="form-label">{{ __('Description') }}</label>
                                <textarea name="description" rows="2" class="form-control"></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="button" class="btn btn-primary" id="saveAccountBtn">{{ __('Save Category') }}</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Select2 on account dropdown
            $('#account-select').select2({
                placeholder: 'Select an expense account',
                allowClear: true,
                width: '100%'
            });

            // Payment status toggle
            const statusSelect = document.getElementById('paymentStatusSelect');
            const methodField = document.getElementById('paymentMethodField');
            const paidOnField = document.getElementById('paidOnField');

            function togglePaymentFields() {
                const paid = statusSelect.value === 'paid';
                methodField.classList.toggle('d-none', !paid);
                paidOnField.classList.toggle('d-none', !paid);
            }

            statusSelect.addEventListener('change', togglePaymentFields);
            togglePaymentFields();

            // Handle account creation via AJAX
            document.getElementById('saveAccountBtn').addEventListener('click', function() {
                const form = document.getElementById('addAccountForm');
                const formData = new FormData(form);
                const saveBtn = this;

                // Basic validation
                const code = formData.get('code');
                const name = formData.get('name');
                if (!code || !name) {
                    alert('Please fill in the required fields (Code and Name).');
                    return;
                }

                // Disable button and show loading state
                saveBtn.disabled = true;
                saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';

                fetch('{{ route('accounts.store.ajax') }}', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Add new option to Select2
                        const newOption = new Option(`${data.account.code} · ${data.account.name}`, data.account.id, true, true);
                        $('#account-select').append(newOption).trigger('change');

                        // Close modal and reset form
                        const modal = bootstrap.Modal.getInstance(document.getElementById('addAccountModal'));
                        modal.hide();
                        form.reset();

                        // Re-enable button
                        saveBtn.disabled = false;
                        saveBtn.innerHTML = 'Save Category';
                    } else {
                        alert('Error creating category. Please try again.');
                        saveBtn.disabled = false;
                        saveBtn.innerHTML = 'Save Category';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error creating category. Please try again.');
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = 'Save Category';
                });
            });

            // Reset form when modal is closed
            document.getElementById('addAccountModal').addEventListener('hidden.bs.modal', function() {
                document.getElementById('addAccountForm').reset();
            });
        });
    </script>
    @endpush
@endsection
