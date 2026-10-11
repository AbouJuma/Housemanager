@extends('layouts.app')

@section('title', __('Create Message Template'))

@section('page-heading')
    <h1 class="h4 mb-0">{{ __('Create Message Template') }}</h1>
@endsection

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('message-templates.store') }}">
                @csrf

                <div class="mb-4">
                    <label for="type" class="form-label fw-bold">{{ __('Template Type') }}</label>
                    <select name="type" id="type" class="form-select @error('type') is-invalid @enderror" required onchange="updateAvailableFields()">
                        <option value="">-- Select Type --</option>
                        <option value="lease_created_tenant" {{ old('type') === 'lease_created_tenant' ? 'selected' : '' }}>Lease Created (Tenant)</option>
                        <option value="tenant_welcome" {{ old('type') === 'tenant_welcome' ? 'selected' : '' }}>Tenant Welcome</option>
                        <option value="landlord_welcome" {{ old('type') === 'landlord_welcome' ? 'selected' : '' }}>Landlord Welcome</option>
                        <option value="buyer_welcome" {{ old('type') === 'buyer_welcome' ? 'selected' : '' }}>Buyer Welcome</option>
                        <option value="staff_welcome" {{ old('type') === 'staff_welcome' ? 'selected' : '' }}>Staff Welcome</option>
                        <option value="lease_payment" {{ old('type') === 'lease_payment' ? 'selected' : '' }}>Lease Payment</option>
                        <option value="buyer_invoice" {{ old('type') === 'buyer_invoice' ? 'selected' : '' }}>Buyer Invoice</option>
                        <option value="landlord_payment_notification" {{ old('type') === 'landlord_payment_notification' ? 'selected' : '' }}>Landlord Payment Notification</option>
                        <option value="tenant_payment_notification" {{ old('type') === 'tenant_payment_notification' ? 'selected' : '' }}>Tenant Payment Notification</option>
                        <option value="lease_landlord_notification" {{ old('type') === 'lease_landlord_notification' ? 'selected' : '' }}>Lease Landlord Notification</option>
                        <option value="buyer_bill" {{ old('type') === 'buyer_bill' ? 'selected' : '' }}>Buyer Bill (Installment Invoice)</option>
                        <option value="invoice_due_soon" {{ old('type') === 'invoice_due_soon' ? 'selected' : '' }}>Rent Invoice Due Soon</option>
                        <option value="invoice_overdue" {{ old('type') === 'invoice_overdue' ? 'selected' : '' }}>Rent Invoice Overdue</option>
                        <option value="installment_due_soon" {{ old('type') === 'installment_due_soon' ? 'selected' : '' }}>Installment Due Soon</option>
                        <option value="installment_overdue" {{ old('type') === 'installment_overdue' ? 'selected' : '' }}>Installment Overdue</option>
                        <option value="sale_created" {{ old('type') === 'sale_created' ? 'selected' : '' }}>Sale Created (Buyer)</option>
                        <option value="sale_payment_landlord" {{ old('type') === 'sale_payment_landlord' ? 'selected' : '' }}>Sale Payment (Landlord)</option>
                        <option value="sale_completed" {{ old('type') === 'sale_completed' ? 'selected' : '' }}>Sale Completed</option>
                    </select>
                    @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-4">
                    <label for="name" class="form-label fw-bold">{{ __('Template Name') }}</label>
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
                    <label for="sms_template" class="form-label fw-bold">{{ __('SMS Template') }}</label>
                    <div class="mb-2">
                        <small class="text-muted">Click on a field to insert it into the template:</small>
                    </div>
                    <div class="mb-3" id="sms-fields">
                        <small class="text-muted">{{ __('Select a template type to see available fields') }}</small>
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
                    <label for="email_subject" class="form-label fw-bold">{{ __('Email Subject') }}</label>
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
                    <label for="email_template" class="form-label fw-bold">{{ __('Email Template (HTML)') }}</label>
                    <div class="mb-2">
                        <small class="text-muted">Click on a field to insert it into the template:</small>
                    </div>
                    <div class="mb-3" id="email-fields">
                        <small class="text-muted">{{ __('Select a template type to see available fields') }}</small>
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
                            <strong>{{ __('Enable this template') }}</strong> - Messages will only be sent if this template is enabled
                        </label>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">{{ __('Create Template') }}</button>
                    <a href="{{ route('message-templates.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        const fieldsByType = @json(\App\Models\MessageTemplate::PLACEHOLDERS);

        function updateAvailableFields() {
            const type = document.getElementById('type').value;
            const fields = fieldsByType[type] || [];
            
            const smsFieldsContainer = document.getElementById('sms-fields');
            const emailFieldsContainer = document.getElementById('email-fields');
            
            if (fields.length === 0) {
                smsFieldsContainer.innerHTML = '<small class="text-muted">{{ __('Select a template type to see available fields') }}</small>';
                emailFieldsContainer.innerHTML = '<small class="text-muted">{{ __('Select a template type to see available fields') }}</small>';
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
