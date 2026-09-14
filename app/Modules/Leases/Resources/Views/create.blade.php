@extends('layouts.app')

@section('title', 'New Lease')

@section('page-heading')
    <h1 class="h4 mb-0">New Lease</h1>
@endsection

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('leases.store') }}">
                @csrf
                @include('leases::_form')

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Create Lease</button>
                    <a href="{{ route('leases.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Tenant Modal -->
    <div class="modal fade" id="addTenantModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Tenant</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addTenantForm">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Type <span class="text-danger">*</span></label>
                                <select name="type" class="form-select" required>
                                    <option value="individual">Individual</option>
                                    <option value="company">Company</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Secondary Phone</label>
                                <input type="text" name="secondary_phone" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" name="date_of_birth" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">ID Type</label>
                                <select name="identification_type" class="form-select">
                                    <option value="">None</option>
                                    <option value="national_id">National ID</option>
                                    <option value="passport">Passport</option>
                                    <option value="driving_license">Driving License</option>
                                    <option value="voter_id">Voter ID</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">ID Number</label>
                                <input type="text" name="identification_number" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Address Line 1</label>
                                <input type="text" name="address_line1" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Address Line 2</label>
                                <input type="text" name="address_line2" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">City</label>
                                <input type="text" name="city" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">State / Region</label>
                                <input type="text" name="state" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Postal Code</label>
                                <input type="text" name="postal_code" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Country Code</label>
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
                                <label class="form-label">Notes</label>
                                <textarea name="notes" rows="2" class="form-control"></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveTenantBtn">Save Tenant</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Select2 on tenant dropdown
            $('#tenant-select').select2({
                placeholder: 'Select a tenant',
                allowClear: true,
                width: '100%'
            });

            // Handle tenant creation via AJAX
            document.getElementById('saveTenantBtn').addEventListener('click', function() {
                const form = document.getElementById('addTenantForm');
                const formData = new FormData(form);
                const saveBtn = this;

                // Basic validation
                const name = formData.get('name');
                const type = formData.get('type');
                if (!name || !type) {
                    alert('Please fill in the required fields (Name and Type).');
                    return;
                }

                // Disable button and show loading state
                saveBtn.disabled = true;
                saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';

                fetch('{{ route('tenants.store.ajax') }}', {
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
                        const newOption = new Option(data.tenant.name, data.tenant.id, true, true);
                        $('#tenant-select').append(newOption).trigger('change');

                        // Close modal and reset form
                        const modal = bootstrap.Modal.getInstance(document.getElementById('addTenantModal'));
                        modal.hide();
                        form.reset();

                        // Re-enable button
                        saveBtn.disabled = false;
                        saveBtn.innerHTML = 'Save Tenant';
                    } else {
                        alert('Error creating tenant. Please try again.');
                        saveBtn.disabled = false;
                        saveBtn.innerHTML = 'Save Tenant';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error creating tenant. Please try again.');
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = 'Save Tenant';
                });
            });

            // Reset form when modal is closed
            document.getElementById('addTenantModal').addEventListener('hidden.bs.modal', function() {
                document.getElementById('addTenantForm').reset();
            });
        });
    </script>
    @endpush
@endsection
