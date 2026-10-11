@extends('layouts.app')

@section('title', __('New Lease'))

@section('page-heading')
    <h1 class="h4 mb-0">{{ __('New Lease') }}</h1>
@endsection

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('leases.store') }}">
                @csrf
                @include('leases::_form')

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">{{ __('Create Lease') }}</button>
                    <a href="{{ route('leases.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Tenant Modal -->
    <div class="modal fade" id="addTenantModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Add New Tenant') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="addTenantError" class="alert alert-danger d-none"></div>
                    <form id="addTenantForm">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('Name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('Type') }} <span class="text-danger">*</span></label>
                                <select name="type" class="form-select" required>
                                    <option value="individual">{{ __('Individual') }}</option>
                                    <option value="company">{{ __('Company') }}</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('Status') }}</label>
                                <select name="status" class="form-select">
                                    <option value="active">{{ __('Active') }}</option>
                                    <option value="inactive">{{ __('Inactive') }}</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('Email') }}</label>
                                <input type="email" name="email" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('Phone') }}</label>
                                <input type="text" name="phone" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('Secondary Phone') }}</label>
                                <input type="text" name="secondary_phone" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('Date of Birth') }}</label>
                                <input type="date" name="date_of_birth" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('ID Type') }}</label>
                                <select name="identification_type" class="form-select">
                                    <option value="">None</option>
                                    <option value="national_id">National ID</option>
                                    <option value="passport">Passport</option>
                                    <option value="driving_license">Driving License</option>
                                    <option value="voter_id">Voter ID</option>
                                    <option value="other">{{ __('Other') }}</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('ID Number') }}</label>
                                <input type="text" name="identification_number" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('Address Line 1') }}</label>
                                <input type="text" name="address_line1" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('Address Line 2') }}</label>
                                <input type="text" name="address_line2" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('City') }}</label>
                                <input type="text" name="city" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('State / Region') }}</label>
                                <input type="text" name="state" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('Postal Code') }}</label>
                                <input type="text" name="postal_code" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('Country Code') }}</label>
                                <input type="text" name="country_code" maxlength="2" class="form-control text-uppercase" placeholder="e.g. TZ">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Emergency Contact Name</label>
                                <input type="text" name="emergency_contact_name" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Emergency Contact Phone</label>
                                <input type="text" name="emergency_contact_phone" class="form-control">
                            </div>
                            <div class="col-12">
                                <label class="form-label">{{ __('Notes') }}</label>
                                <textarea name="notes" rows="2" class="form-control"></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="button" class="btn btn-primary" id="saveTenantBtn">{{ __('Save Tenant') }}</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // #tenant-select's select2 is initialized once, in leases::_form.
            const modalEl = document.getElementById('addTenantModal');
            const form = document.getElementById('addTenantForm');
            const errorBox = document.getElementById('addTenantError');
            const saveBtn = document.getElementById('saveTenantBtn');

            function showErrors(messages) {
                errorBox.innerHTML = '';
                const list = document.createElement('ul');
                list.className = 'mb-0 ps-3';
                messages.forEach(function(message) {
                    const item = document.createElement('li');
                    item.textContent = message;
                    list.appendChild(item);
                });
                errorBox.appendChild(list);
                errorBox.classList.remove('d-none');
            }

            function clearErrors() {
                errorBox.classList.add('d-none');
                errorBox.innerHTML = '';
                form.querySelectorAll('.is-invalid').forEach(function(el) { el.classList.remove('is-invalid'); });
            }

            saveBtn.addEventListener('click', function() {
                clearErrors();
                const formData = new FormData(form);

                if (!formData.get('name') || !formData.get('type')) {
                    showErrors(['Name and Type are required.']);
                    return;
                }

                saveBtn.disabled = true;
                saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';

                fetch('{{ route('tenants.store.ajax') }}', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: formData,
                })
                .then(async function(response) {
                    const data = await response.json().catch(function() { return {}; });

                    if (response.status === 419) {
                        throw ['Your session has expired. Please refresh the page and try again.'];
                    }
                    if (response.status === 403) {
                        throw ['You do not have permission to create tenants.'];
                    }
                    if (response.status === 422 && data.errors) {
                        // Highlight the offending fields and list the
                        // server's own messages instead of a generic alert.
                        Object.keys(data.errors).forEach(function(field) {
                            const input = form.querySelector('[name="' + field + '"]');
                            if (input) input.classList.add('is-invalid');
                        });
                        throw Object.values(data.errors).flat();
                    }
                    if (!response.ok || !data.success) {
                        throw [data.message || 'Could not create the tenant (server error ' + response.status + ').'];
                    }

                    return data;
                })
                .then(function(data) {
                    const newOption = new Option(data.tenant.name, data.tenant.id, true, true);
                    $('#tenant-select').append(newOption).trigger('change');
                    bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                })
                .catch(function(messages) {
                    showErrors(Array.isArray(messages) ? messages : ['Network error - please check your connection and try again.']);
                })
                .finally(function() {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = 'Save Tenant';
                });
            });

            modalEl.addEventListener('hidden.bs.modal', function() {
                form.reset();
                clearErrors();
            });
        });
    </script>
    @endpush
@endsection
