@extends('layouts.app')

@section('title', 'Create Message Template')

@section('page-heading')
    <h1 class="h4 mb-0">Create Message Template</h1>
@endsection

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('message-templates.store') }}">
                @csrf

                <div class="mb-4">
                    <label for="type" class="form-label fw-bold">Template Type</label>
                    <select name="type" id="type" class="form-select @error('type') is-invalid @enderror" required onchange="updateAvailableFields()">
                        <option value="">-- Select Type --</option>
                        <option value="tenant_welcome" {{ old('type') === 'tenant_welcome' ? 'selected' : '' }}>Tenant Welcome</option>
                        <option value="landlord_welcome" {{ old('type') === 'landlord_welcome' ? 'selected' : '' }}>Landlord Welcome</option>
                        <option value="buyer_welcome" {{ old('type') === 'buyer_welcome' ? 'selected' : '' }}>Buyer Welcome</option>
                        <option value="lease_payment" {{ old('type') === 'lease_payment' ? 'selected' : '' }}>Lease Payment</option>
                        <option value="buyer_invoice" {{ old('type') === 'buyer_invoice' ? 'selected' : '' }}>Buyer Invoice</option>
                        <option value="landlord_payment_notification" {{ old('type') === 'landlord_payment_notification' ? 'selected' : '' }}>Landlord Payment Notification</option>
                        <option value="tenant_payment_notification" {{ old('type') === 'tenant_payment_notification' ? 'selected' : '' }}>Tenant Payment Notification</option>
                    </select>
                    @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-4">
                    <label for="name" class="form-label fw-bold">Template Name</label>
                    <input 
                        type="text" 
                        name="name" 
                        id="name" 
                        class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name') }}"
                        required
                    >
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-4">
                    <label for="sms_template" class="form-label fw-bold">SMS Template</label>
                    <div class="mb-2">
                        <small class="text-muted">Click on a field to insert it into the template:</small>
                    </div>
                    <div class="mb-3" id="sms-fields">
                        <small class="text-muted">Select a template type to see available fields</small>
                    </div>
                    <textarea 
                        name="sms_template" 
                        id="sms_template" 
                        rows="6" 
                        class="form-control @error('sms_template') is-invalid @enderror"
                        placeholder="Use {placeholder} for dynamic data"
                    >{{ old('sms_template') }}</textarea>
                    @error('sms_template') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-4">
                    <label for="email_subject" class="form-label fw-bold">Email Subject</label>
                    <textarea 
                        name="email_subject" 
                        id="email_subject" 
                        rows="2" 
                        class="form-control @error('email_subject') is-invalid @enderror"
                        placeholder="Email subject line"
                    >{{ old('email_subject') }}</textarea>
                    @error('email_subject') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-4">
                    <label for="email_template" class="form-label fw-bold">Email Template (HTML)</label>
                    <div class="mb-2">
                        <small class="text-muted">Click on a field to insert it into the template:</small>
                    </div>
                    <div class="mb-3" id="email-fields">
                        <small class="text-muted">Select a template type to see available fields</small>
                    </div>
                    <textarea 
                        name="email_template" 
                        id="email_template" 
                        rows="10" 
                        class="form-control @error('email_template') is-invalid @enderror"
                        placeholder="Use {placeholder} for dynamic data"
                    >{{ old('email_template') }}</textarea>
                    @error('email_template') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-4">
                    <div class="form-check form-switch">
                        <input 
                            class="form-check-input" 
                            type="checkbox" 
                            name="is_active" 
                            id="is_active" 
                            value="1"
                            {{ old('is_active', true) ? 'checked' : '' }}
                        >
                        <label class="form-check-label" for="is_active">
                            <strong>Enable this template</strong> - Messages will only be sent if this template is enabled
                        </label>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Create Template</button>
                    <a href="{{ route('message-templates.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        const fieldsByType = {
            'tenant_welcome': ['tenant_name', 'company_name', 'phone', 'control_number', 'bank_name', 'account_number', 'rent_amount', 'property_name', 'unit_number'],
            'landlord_welcome': ['landlord_name', 'company_name', 'property_names'],
            'buyer_welcome': ['buyer_name', 'company_name', 'unit_number', 'property_name', 'amount', 'control_number', 'bank_name', 'account_number'],
            'lease_payment': ['amount', 'lease_duration', 'balance', 'lease_number', 'tenant_name', 'property_name', 'unit_number'],
            'buyer_invoice': ['amount', 'balance', 'payment_terms', 'invoice_number', 'buyer_name', 'property_name', 'unit_number'],
            'landlord_payment_notification': ['amount', 'tenant_name', 'unit_number', 'property_name', 'payment_date'],
            'tenant_payment_notification': ['amount', 'unit_number', 'property_name', 'payment_date', 'balance'],
        };

        function updateAvailableFields() {
            const type = document.getElementById('type').value;
            const fields = fieldsByType[type] || [];
            
            const smsFieldsContainer = document.getElementById('sms-fields');
            const emailFieldsContainer = document.getElementById('email-fields');
            
            if (fields.length === 0) {
                smsFieldsContainer.innerHTML = '<small class="text-muted">Select a template type to see available fields</small>';
                emailFieldsContainer.innerHTML = '<small class="text-muted">Select a template type to see available fields</small>';
                return;
            }
            
            let smsButtons = '';
            let emailButtons = '';
            
            fields.forEach(function(field) {
                smsButtons += '<button type="button" class="btn btn-sm btn-outline-primary me-1 mb-1 field-picker" data-field="' + field + '">' + field + '</button>';
                emailButtons += '<button type="button" class="btn btn-sm btn-outline-primary me-1 mb-1 field-picker-email" data-field="' + field + '">' + field + '</button>';
            });
            
            smsFieldsContainer.innerHTML = smsButtons;
            emailFieldsContainer.innerHTML = emailButtons;
            
            // Re-attach event listeners
            attachFieldPickers();
        }

        function attachFieldPickers() {
            document.querySelectorAll('.field-picker').forEach(function(button) {
                button.addEventListener('click', function() {
                    const field = this.getAttribute('data-field');
                    const textarea = document.getElementById('sms_template');
                    const start = textarea.selectionStart;
                    const end = textarea.selectionEnd;
                    const text = textarea.value;
                    const placeholder = '{' + field + '}';
                    
                    textarea.value = text.substring(0, start) + placeholder + text.substring(end);
                    textarea.selectionStart = textarea.selectionEnd = start + placeholder.length;
                    textarea.focus();
                });
            });

            document.querySelectorAll('.field-picker-email').forEach(function(button) {
                button.addEventListener('click', function() {
                    const field = this.getAttribute('data-field');
                    const textarea = document.getElementById('email_template');
                    const start = textarea.selectionStart;
                    const end = textarea.selectionEnd;
                    const text = textarea.value;
                    const placeholder = '{' + field + '}';
                    
                    textarea.value = text.substring(0, start) + placeholder + text.substring(end);
                    textarea.selectionStart = textarea.selectionEnd = start + placeholder.length;
                    textarea.focus();
                });
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            updateAvailableFields();
        });
    </script>
@endsection
