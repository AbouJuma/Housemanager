@extends('layouts.app')

@section('title', 'Create Multiple Units')

@section('page-heading')
    <h1 class="h4 mb-0">Create Multiple Units</h1>
@endsection

@section('content')
    <p class="text-secondary mb-3">For {{ $building->name }} · {{ $building->property->name }}</p>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <button type="button" class="btn btn-outline-primary" id="addUnitBtn">
                        <i class="bi bi-plus-lg"></i> Add Unit Row
                    </button>
                    <button type="button" class="btn btn-outline-secondary ms-2" id="clearAllBtn">
                        <i class="bi bi-x-lg"></i> Clear All
                    </button>
                </div>
                <div class="text-secondary">
                    <span id="unitCount">0</span> unit(s) added
                </div>
            </div>

            <form id="multiUnitForm">
                @csrf
                <div id="unitsContainer">
                    <!-- Unit rows will be added here -->
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button type="button" class="btn btn-primary" id="saveAllBtn">
                        <i class="bi bi-save"></i> Create All Units
                    </button>
                    <a href="{{ route('buildings.show', $building) }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Unit Row Template -->
    <template id="unitRowTemplate">
        <div class="unit-row card mb-3" data-index="__INDEX__">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Unit Number <span class="text-danger">*</span></label>
                        <input type="text" name="units[__INDEX__][unit_number]" class="form-control unit-number" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Type <span class="text-danger">*</span></label>
                        <select name="units[__INDEX__][type]" class="form-select unit-type" required>
                            <option value="">Choose…</option>
                            <option value="apartment">Apartment</option>
                            <option value="studio">Studio</option>
                            <option value="office">Office</option>
                            <option value="shop">Shop</option>
                            <option value="warehouse_space">Warehouse Space</option>
                            <option value="room">Room</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Bedrooms</label>
                        <input type="number" min="0" name="units[__INDEX__][bedrooms]" class="form-control" value="0">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Bathrooms</label>
                        <input type="number" min="0" name="units[__INDEX__][bathrooms]" class="form-control" value="0">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Area</label>
                        <input type="number" step="0.01" min="0" name="units[__INDEX__][area]" class="form-control">
                    </div>
                    <div class="col-12">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Area Unit</label>
                                <select name="units[__INDEX__][area_unit]" class="form-select">
                                    <option value="sqm">SQM</option>
                                    <option value="sqft">SQFT</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Rent Amount</label>
                                <input type="number" step="0.01" min="0" name="units[__INDEX__][rent_amount]" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select name="units[__INDEX__][status]" class="form-select">
                                    <option value="vacant">Vacant</option>
                                    <option value="occupied">Occupied</option>
                                    <option value="under_maintenance">Under Maintenance</option>
                                    <option value="reserved">Reserved</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="button" class="btn btn-outline-danger w-100 remove-unit-btn">
                                    <i class="bi bi-trash"></i> Remove
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="units[__INDEX__][description]" rows="2" class="form-control"></textarea>
                    </div>
                </div>
            </div>
        </div>
    </template>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let nextIndex = 0;
            const container = document.getElementById('unitsContainer');
            const template = document.getElementById('unitRowTemplate');
            const addBtn = document.getElementById('addUnitBtn');
            const clearBtn = document.getElementById('clearAllBtn');
            const saveBtn = document.getElementById('saveAllBtn');
            const countDisplay = document.getElementById('unitCount');

            function updateCount() {
                countDisplay.textContent = container.querySelectorAll('.unit-row').length;
            }

            function addUnitRow() {
                const wrapper = document.createElement('div');
                wrapper.innerHTML = template.innerHTML.replaceAll('__INDEX__', String(nextIndex));
                container.appendChild(wrapper.firstElementChild);
                nextIndex++;
                updateCount();
            }

            function removeUnitRow(button) {
                const row = button.closest('.unit-row');
                row.remove();
                updateCount();
            }

            addBtn.addEventListener('click', addUnitRow);

            container.addEventListener('click', function(e) {
                const removeBtn = e.target.closest('.remove-unit-btn');
                if (removeBtn) {
                    removeUnitRow(removeBtn);
                }
            });

            clearBtn.addEventListener('click', function() {
                if (container.querySelectorAll('.unit-row').length > 0 && confirm('Remove all unit rows?')) {
                    container.innerHTML = '';
                    nextIndex = 0;
                    updateCount();
                }
            });

            saveBtn.addEventListener('click', function() {
                const rows = container.querySelectorAll('.unit-row');
                if (rows.length === 0) {
                    alert('Please add at least one unit.');
                    return;
                }

                // Validate required fields
                let isValid = true;
                rows.forEach((row, index) => {
                    const unitNumber = row.querySelector('.unit-number').value.trim();
                    const unitType = row.querySelector('.unit-type').value;
                    
                    if (!unitNumber || !unitType) {
                        isValid = false;
                        row.classList.add('border', 'border-danger');
                    } else {
                        row.classList.remove('border', 'border-danger');
                    }
                });

                if (!isValid) {
                    alert('Please fill in all required fields (Unit Number and Type) for each unit.');
                    return;
                }

                // Disable button and show loading state
                saveBtn.disabled = true;
                saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creating Units...';

                const formData = new FormData(document.getElementById('multiUnitForm'));

                fetch('{{ route('units.store_multiple', $building) }}', {
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
                        const message = data.failed > 0 
                            ? `Created ${data.total} unit(s). ${data.failed} failed.` 
                            : `Successfully created ${data.total} unit(s).`;
                        
                        if (data.errors.length > 0) {
                            console.error('Errors:', data.errors);
                            alert(message + ' Check console for details.');
                        } else {
                            alert(message);
                        }

                        window.location.href = '{{ route('buildings.show', $building) }}';
                    } else {
                        alert('Error creating units. Please try again.');
                        saveBtn.disabled = false;
                        saveBtn.innerHTML = '<i class="bi bi-save"></i> Create All Units';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error creating units. Please try again.');
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<i class="bi bi-save"></i> Create All Units';
                });
            });

            // Add one initial row
            addUnitRow();
        });
    </script>
    @endpush
@endsection
