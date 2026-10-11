@php($property = $property ?? null)

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">{{ __('Property Name') }}</label>
        <input type="text" name="name" value="{{ old('name', $property?->name) }}" class="form-control @error('name') is-invalid @enderror" required>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ __('Type') }}</label>
        <select name="type" class="form-select @error('type') is-invalid @enderror" required>
            <option value="">{{ __('Choose a type…') }}</option>
            @foreach (['residential', 'commercial', 'mixed_use', 'warehouse', 'retail', 'hotel', 'hostel', 'industrial', 'land', 'other'] as $type)
                <option value="{{ $type }}" @selected(old('type', $property?->type) === $type)>{{ __(ucfirst(str_replace('_', ' ', $type))) }}</option>
            @endforeach
        </select>
        @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">{{ __('Landlord') }} <span class="text-secondary fw-normal">{{ __('(optional)') }}</span></label>
        <div class="d-flex gap-2">
            <select name="landlord_id" id="landlordSelect" class="form-select @error('landlord_id') is-invalid @enderror flex-grow-1">
                <option value="">&mdash; None &mdash;</option>
                @foreach ($landlords as $landlord)
                    <option value="{{ $landlord->id }}" @selected((int) old('landlord_id', $property?->landlord_id) === $landlord->id)>{{ $landlord->name }}</option>
                @endforeach
            </select>
            @can('create', \App\Modules\Landlords\Models\Landlord::class)
                <button type="button" class="btn btn-outline-secondary flex-shrink-0" data-bs-toggle="modal" data-bs-target="#landlordModal">
                    <i class="bi bi-plus-lg"></i> {{ __('Add New') }}
                </button>
            @endcan
        </div>
        @error('landlord_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    @if ($property)
        <div class="col-md-6">
            <label class="form-label">{{ __('Status') }}</label>
            <select name="status" class="form-select @error('status') is-invalid @enderror">
                @foreach (['active', 'inactive', 'under_maintenance', 'archived'] as $status)
                    <option value="{{ $status }}" @selected(old('status', $property->status) === $status)>{{ __(ucfirst(str_replace('_', ' ', $status))) }}</option>
                @endforeach
            </select>
            @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    @endif

    <div class="col-12">
        <label class="form-label">{{ __('Description') }}</label>
        <textarea name="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description', $property?->description) }}</textarea>
        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- Location: searchable Region -> District -> Area pickers fed by
         config/tanzania.php. Region is saved in `state`, district in
         `city`, neighbourhood/ward in `area`; each picker also accepts a
         typed value, for places not in the list. --}}
    <div class="col-12 pt-2">
        <div class="d-flex align-items-center gap-2 fw-semibold">
            <span class="icon-tile icon-tile-sky" style="width: 30px; height: 30px; font-size: .95rem;"><i class="bi bi-geo-alt"></i></span>
            {{ __('Location') }}
        </div>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="regionSelect">{{ __('Region') }}</label>
        <select name="state" id="regionSelect" class="form-select @error('state') is-invalid @enderror" data-selected="{{ old('state', $property?->state) }}">
            <option value=""></option>
        </select>
        @error('state') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="districtSelect">{{ __('District') }}</label>
        <select name="city" id="districtSelect" class="form-select @error('city') is-invalid @enderror" data-selected="{{ old('city', $property?->city) }}">
            <option value=""></option>
        </select>
        @error('city') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="areaSelect">{{ __('Area / Ward') }}</label>
        <select name="area" id="areaSelect" class="form-select @error('area') is-invalid @enderror" data-selected="{{ old('area', $property?->area) }}">
            <option value=""></option>
        </select>
        @error('area') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">{{ __('Address Line 1') }}</label>
        <input type="text" name="address_line1" value="{{ old('address_line1', $property?->address_line1) }}" class="form-control @error('address_line1') is-invalid @enderror">
        @error('address_line1') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ __('Address Line 2') }}</label>
        <input type="text" name="address_line2" value="{{ old('address_line2', $property?->address_line2) }}" class="form-control @error('address_line2') is-invalid @enderror">
        @error('address_line2') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">{{ __('Postal Code') }}</label>
        <input type="text" name="postal_code" value="{{ old('postal_code', $property?->postal_code) }}" class="form-control @error('postal_code') is-invalid @enderror">
        @error('postal_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ __('Country Code') }}</label>
        <input type="text" name="country_code" maxlength="2" value="{{ old('country_code', $property?->country_code ?? 'TZ') }}" class="form-control text-uppercase @error('country_code') is-invalid @enderror" placeholder="e.g. TZ">
        @error('country_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __('Total Area') }}</label>
        <input type="number" step="0.01" name="total_area" value="{{ old('total_area', $property?->total_area) }}" class="form-control @error('total_area') is-invalid @enderror">
        @error('total_area') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __('Area Unit') }}</label>
        <select name="area_unit" class="form-select @error('area_unit') is-invalid @enderror">
            @foreach (['sqm', 'sqft'] as $unit)
                <option value="{{ $unit }}" @selected(old('area_unit', $property?->area_unit ?? 'sqm') === $unit)>{{ strtoupper($unit) }}</option>
            @endforeach
        </select>
        @error('area_unit') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- Map coordinates aren't needed to register a property - only
         offered when editing one (still optional). --}}
    @if ($property)
        <div class="col-md-6">
            <label class="form-label">{{ __('Latitude') }} <span class="text-secondary fw-normal">{{ __('(optional)') }}</span></label>
            <input type="number" step="0.0000001" name="latitude" value="{{ old('latitude', $property->latitude) }}" class="form-control @error('latitude') is-invalid @enderror">
            @error('latitude') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">{{ __('Longitude') }} <span class="text-secondary fw-normal">{{ __('(optional)') }}</span></label>
            <input type="number" step="0.0000001" name="longitude" value="{{ old('longitude', $property->longitude) }}" class="form-control @error('longitude') is-invalid @enderror">
            @error('longitude') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    @endif
</div>

@can('create', \App\Modules\Landlords\Models\Landlord::class)
    {{-- Pushed to the layout's 'modals' stack, not rendered in place - this
         partial is included inside the property <form>, and the modal's
         `required` inputs sitting (empty, hidden) inside that form made
         the browser silently block every "Create Property" submit. --}}
    @push('modals')
    <div class="modal fade" id="landlordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Add New Landlord') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="landlordModalError" class="alert alert-danger d-none"></div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">{{ __('Name') }} <span class="text-danger">*</span></label>
                            <input type="text" id="landlordModalName" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Type') }}</label>
                            <select id="landlordModalType" class="form-select">
                                <option value="individual" selected>{{ __('Individual') }}</option>
                                <option value="company">{{ __('Company') }}</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Phone') }} <span class="text-danger">*</span></label>
                            <input type="text" id="landlordModalPhone" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('Email') }}</label>
                            <input type="email" id="landlordModalEmail" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="button" class="btn btn-primary" id="saveLandlord">
                        <span id="saveLandlordSpinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                        {{ __('Save Landlord') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endpush

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const landlordSelectEl = document.getElementById('landlordSelect');
            $(landlordSelectEl).select2({
                placeholder: @json(__('— None —')),
                allowClear: true,
                width: '100%',
            });

            const landlordModalEl = document.getElementById('landlordModal');
            const landlordModal = new bootstrap.Modal(landlordModalEl);
            const errorBox = document.getElementById('landlordModalError');
            const nameInput = document.getElementById('landlordModalName');
            const phoneInput = document.getElementById('landlordModalPhone');
            const emailInput = document.getElementById('landlordModalEmail');
            const typeInput = document.getElementById('landlordModalType');
            const saveBtn = document.getElementById('saveLandlord');
            const saveSpinner = document.getElementById('saveLandlordSpinner');

            function resetLandlordModal() {
                nameInput.value = '';
                phoneInput.value = '';
                emailInput.value = '';
                typeInput.value = 'individual';
                errorBox.classList.add('d-none');
                errorBox.textContent = '';
            }

            landlordModalEl.addEventListener('hidden.bs.modal', resetLandlordModal);

            saveBtn.addEventListener('click', function() {
                if (!nameInput.value.trim() || !phoneInput.value.trim()) {
                    errorBox.textContent = 'Name and phone are required.';
                    errorBox.classList.remove('d-none');
                    return;
                }

                saveBtn.disabled = true;
                saveSpinner.classList.remove('d-none');
                errorBox.classList.add('d-none');

                fetch('{{ route('landlords.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({
                        name: nameInput.value.trim(),
                        type: typeInput.value,
                        phone: phoneInput.value.trim(),
                        email: emailInput.value.trim() || null,
                    }),
                })
                .then(async function(response) {
                    const data = await response.json();
                    if (!response.ok || !data.success) {
                        throw data;
                    }
                    return data;
                })
                .then(function(data) {
                    const option = new Option(data.landlord.name, data.landlord.id, true, true);
                    landlordSelectEl.appendChild(option);
                    $(landlordSelectEl).trigger('change');
                    landlordModal.hide();
                })
                .catch(function(data) {
                    const messages = data && data.errors
                        ? Object.values(data.errors).flat().join(' ')
                        : (data && data.message) || 'Could not create landlord.';
                    errorBox.textContent = messages;
                    errorBox.classList.remove('d-none');
                })
                .finally(function() {
                    saveBtn.disabled = false;
                    saveSpinner.classList.add('d-none');
                });
            });
        });
    </script>
    @endpush
@endcan

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const locations = @json(config('tanzania'));
        const $region = $('#regionSelect');
        const $district = $('#districtSelect');
        const $area = $('#areaSelect');

        // tags: true lets staff type a place that isn't in the list (a new
        // ward, or a property outside Tanzania) while still searching it.
        const pickerOptions = (placeholder) => ({ placeholder, allowClear: true, tags: true, width: '100%' });

        function fill($select, values, selected) {
            $select.empty().append(new Option('', ''));
            values.forEach((value) => $select.append(new Option(value, value, false, value === selected)));
            // Keep a saved value that isn't in the list (typed earlier).
            if (selected && ! values.includes(selected)) {
                $select.append(new Option(selected, selected, true, true));
            }
            $select.trigger('change.select2');
        }

        function districtsFor(region) {
            return locations.regions[region] || [];
        }

        function areasFor(region, district) {
            if (district) {
                return locations.areas[district] || [];
            }
            // No district yet: suggest every listed area in the region.
            return districtsFor(region).flatMap((d) => locations.areas[d] || []);
        }

        $region.select2(pickerOptions(@json(__('Search region…'))));
        $district.select2(pickerOptions(@json(__('Pick a region first, or type…'))));
        $area.select2(pickerOptions(@json(__('Search or type an area…'))));

        fill($region, Object.keys(locations.regions), $region.data('selected') || '');
        fill($district, districtsFor($region.val()), $district.data('selected') || '');
        fill($area, areasFor($region.val(), $district.val()), $area.data('selected') || '');

        $region.on('change', function () {
            fill($district, districtsFor($region.val()), '');
            fill($area, areasFor($region.val(), ''), '');
        });

        $district.on('change', function () {
            // Typing a district that belongs to another region switches the region too.
            const owner = Object.keys(locations.regions).find((r) => locations.regions[r].includes($district.val()));
            if (owner && owner !== $region.val()) {
                $region.val(owner).trigger('change.select2');
            }
            fill($area, areasFor($region.val(), $district.val()), '');
        });
    });
</script>
@endpush
