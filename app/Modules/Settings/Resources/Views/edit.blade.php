@extends('layouts.app')

@section('title', __('Settings'))

@section('page-heading')
    <h1 class="h4 mb-0">{{ __('Settings') }}</h1>
@endsection

@section('content')
    <form method="POST" action="{{ route('settings.update') }}" style="max-width: 640px;">
        @csrf
        @method('PUT')

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h2 class="h6 mb-3">General</h2>

                <div class="mb-3">
                    <label for="date_format" class="form-label">Date Format</label>
                    <input id="date_format" type="text" name="date_format" value="{{ old('date_format', $values['date_format']) }}" class="form-control @error('date_format') is-invalid @enderror" required>
                    <div class="form-text">PHP date() format, e.g. <code>M j, Y</code> &rarr; {{ now()->format($values['date_format']) }}</div>
                    @error('date_format') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h2 class="h6 mb-1">Payment details in tenant messages</h2>
                <p class="text-secondary small mb-3">The bank account tenants are told to pay into (lease-created message: {bank_name}, {account_name}, {account_number}).</p>

                <div class="mb-3">
                    <label for="payment_details_source" class="form-label">Use which bank account?</label>
                    <select id="payment_details_source" name="payment_details_source" class="form-select @error('payment_details_source') is-invalid @enderror">
                        <option value="landlord" @selected(old('payment_details_source', $values['payment_details_source']) === 'landlord')>The landlord's own account (set on each landlord)</option>
                        <option value="company" @selected(old('payment_details_source', $values['payment_details_source']) === 'company')>The company account (below)</option>
                    </select>
                    <div class="form-text">If the chosen account has no details saved yet, the other one is used so the message is never left blank.</div>
                    @error('payment_details_source') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <h3 class="h6 mt-4 mb-3">Company bank account</h3>

                <div class="mb-3">
                    <label for="company_bank_name" class="form-label">Bank name</label>
                    <input id="company_bank_name" type="text" name="company_bank_name" value="{{ old('company_bank_name', $values['company_bank_name']) }}" class="form-control @error('company_bank_name') is-invalid @enderror">
                    @error('company_bank_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label for="company_bank_account_name" class="form-label">Account name</label>
                    <input id="company_bank_account_name" type="text" name="company_bank_account_name" value="{{ old('company_bank_account_name', $values['company_bank_account_name']) }}" class="form-control @error('company_bank_account_name') is-invalid @enderror">
                    @error('company_bank_account_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label for="company_bank_account_number" class="form-label">Account number</label>
                    <input id="company_bank_account_number" type="text" name="company_bank_account_number" value="{{ old('company_bank_account_number', $values['company_bank_account_number']) }}" class="form-control @error('company_bank_account_number') is-invalid @enderror">
                    @error('company_bank_account_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">{{ __('Save Changes') }}</button>
    </form>
@endsection
