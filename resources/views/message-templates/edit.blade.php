@extends('layouts.app')

@section('title', __('Edit Message Template'))

@section('page-heading')
    <h1 class="h4 mb-0">{{ __('Edit Message Template') }}</h1>
@endsection

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('message-templates.update', $template->id) }}">
                @csrf
                @method('PUT')

                <div class="mb-4">
                    <label class="form-label fw-bold">{{ __('Template Type') }}</label>
                    <div class="form-control-plaintext">
                        <span class="badge bg-primary">
                            {{ Str::title(str_replace('_', ' ', $template->type)) }}
                        </span>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">{{ __('Template Name') }}</label>
                    <div class="form-control-plaintext">{{ $template->name }}</div>
                </div>

                @if ($usage = \App\Models\MessageTemplate::usage($template->type))
                    <div class="alert alert-info">
                        <strong>Where this is sent:</strong> {{ $usage['module'] }} &rarr; to {{ $usage['recipient'] }}.<br>
                        {{ $usage['when'] }}
                    </div>
                @endif

                <div class="mb-4">
                    <label for="sms_template" class="form-label fw-bold">{{ __('SMS Template') }}</label>
                    <div class="mb-2">
                        <small class="text-muted">Click on a field to insert it into the template:</small>
                    </div>
                    <div class="mb-3">
                        @php
                            $availableFields = \App\Models\MessageTemplate::placeholders($template->type);
                        @endphp
                        @foreach($availableFields as $field)
                            <button type="button" class="btn btn-sm btn-outline-primary me-1 mb-1 field-picker" data-field="{{ $field }}">
                                {{ $field }}
                            </button>
                        @endforeach
                    </div>
                    <textarea 
                        name="sms_template" 
                        id="sms_template" 
                        rows="6" 
                        class="form-control @error('sms_template') is-invalid @enderror"
                        placeholder="Use {placeholder} for dynamic data"
                    >{{ old('sms_template', $template->sms_template) }}</textarea>
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
                    >{{ old('email_subject', $template->email_subject) }}</textarea>
                    @error('email_subject') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-4">
                    <label for="email_template" class="form-label fw-bold">{{ __('Email Template (HTML)') }}</label>
                    <div class="mb-2">
                        <small class="text-muted">Click on a field to insert it into the template:</small>
                    </div>
                    <div class="mb-3">
                        @foreach($availableFields as $field)
                            <button type="button" class="btn btn-sm btn-outline-primary me-1 mb-1 field-picker-email" data-field="{{ $field }}">
                                {{ $field }}
                            </button>
                        @endforeach
                    </div>
                    <textarea 
                        name="email_template" 
                        id="email_template" 
                        rows="10" 
                        class="form-control @error('email_template') is-invalid @enderror"
                        placeholder="Use {placeholder} for dynamic data"
                    >{{ old('email_template', $template->email_template) }}</textarea>
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
                            {{ old('is_active', $template->is_active) ? 'checked' : '' }}
                        >
                        <label class="form-check-label" for="is_active">
                            <strong>{{ __('Enable this template') }}</strong> - Messages will only be sent if this template is enabled
                        </label>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Update Template</button>
                    <a href="{{ route('message-templates.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Field picker for SMS template
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

            // Field picker for email template
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
        });
    </script>
@endsection
