@php($lease = $lease ?? null)

<div class="row g-3">
    @if (! $lease)
        <div class="col-md-6">
            <label class="form-label">{{ __('Tenant') }}</label>
            <select name="tenant_id" id="tenant-select" class="form-select @error('tenant_id') is-invalid @enderror" required>
                <option value="">&mdash; Select a tenant &mdash;</option>
                @foreach ($tenants as $tenant)
                    <option value="{{ $tenant->id }}" @selected((int) old('tenant_id') === $tenant->id)>{{ $tenant->name }}</option>
                @endforeach
            </select>
            <div class="mt-1">
                @if ($addTenantUrl ?? null)
                    {{-- Landlord portal: their own add-tenant page. --}}
                    <a href="{{ $addTenantUrl }}" class="btn btn-sm btn-link text-primary p-0"><i class="bi bi-plus-circle"></i> {{ __('Add new tenant') }}</a>
                @else
                    <button type="button" class="btn btn-sm btn-link text-primary p-0" data-bs-toggle="modal" data-bs-target="#addTenantModal">
                        <i class="bi bi-plus-circle"></i> {{ __('Add new tenant') }}
                    </button>
                @endif
            </div>
            @error('tenant_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            @if ($tenants->isEmpty())
                <div class="form-text text-warning">No tenants available - every tenant is either blacklisted or doesn't exist yet.</div>
            @endif
        </div>

        @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Initialize Select2 for tenant dropdown (basic version)
                $('#tenant-select').select2({
                    placeholder: 'Select a tenant',
                    allowClear: true,
                    width: '100%'
                });
                $('#unit-select').select2({
                    placeholder: 'Select a vacant unit',
                    allowClear: true,
                    width: '100%'
                });
            });
        </script>
        @endpush
        <div class="col-md-6">
            <label class="form-label">{{ __('Unit') }}</label>
            <select name="unit_id" id="unit-select" class="form-select @error('unit_id') is-invalid @enderror" required>
                <option value="">&mdash; Select a vacant unit &mdash;</option>
                @foreach ($units as $unit)
                    <option value="{{ $unit->id }}" data-rent-amount="{{ $unit->rent_amount }}" data-rent-period="{{ $unit->rent_period ?? 'monthly' }}" @selected((int) old('unit_id') === $unit->id || (! old('unit_id') && $unit->uuid === $selectedUnitUuid))>
                        {{ $unit->unit_number }} &middot; {{ $unit->building->name }} &middot; {{ $unit->building->property->name }}@if ($unit->rent_amount) &middot; {{ __('Rent') }} {{ number_format((float) $unit->rent_amount, 2) }} / {{ ['monthly' => __('month'), 'quarterly' => __('quarter'), 'semi_annual' => __('half year'), 'annual' => __('year')][$unit->rent_period ?? 'monthly'] ?? __('month') }}@endif
                    </option>
                @endforeach
            </select>
            @error('unit_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            @if ($units->isEmpty())
                <div class="form-text text-warning">No vacant units available right now.</div>
            @endif
        </div>

        @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // Works out the rent per billing period from the unit's own
                // price (a 500,000/month unit billed quarterly = 1,500,000) and
                // the end date from the start date and number of periods. Both
                // stay editable.
                const months = { monthly: 1, quarterly: 3, semi_annual: 6, annual: 12 };
                const cycleNames = { monthly: @json(__('month')), quarterly: @json(__('quarter')), semi_annual: @json(__('half year')), annual: @json(__('year')) };
                const unitSelect = document.getElementById('unit-select');
                const cycleSelect = document.querySelector('select[name="billing_cycle"]');
                const startInput = document.querySelector('input[name="start_date"]');
                const periodsInput = document.querySelector('input[name="periods"]');
                const endInput = document.querySelector('input[name="end_date"]');
                const rentInput = document.querySelector('input[name="rent_amount"]');
                const rentHint = document.getElementById('rent-amount-hint');
                const totalHint = document.getElementById('lease-total-hint');
                const money = (n) => Number(n).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                const rentValue = () => parseFloat(String(rentInput.value || '').replace(/,/g, '')) || 0;

                function applyTotal() {
                    if (!totalHint) return;
                    const periods = Math.max(1, parseInt(periodsInput.value, 10) || 1);
                    const rent = rentValue();
                    totalHint.textContent = rent > 0
                        ? @json(__('Total for the lease')) + ': ' + periods + ' × ' + money(rent) + ' = ' + money(rent * periods)
                        : '';
                }

                function applyRent() {
                    const option = unitSelect.options[unitSelect.selectedIndex];
                    const unitRent = option ? parseFloat(option.dataset.rentAmount) : NaN;
                    const unitPeriod = (option && option.dataset.rentPeriod) || 'monthly';
                    const cycle = cycleSelect.value || 'monthly';

                    if (!isNaN(unitRent) && unitRent > 0) {
                        const perCycle = Math.round(unitRent / months[unitPeriod] * months[cycle] * 100) / 100;
                        window.setMoneyValue(rentInput, perCycle);
                        rentHint.textContent = unitPeriod === cycle
                            ? @json(__('From the unit\'s rent - you can adjust it.'))
                            : money(unitRent) + ' / ' + cycleNames[unitPeriod] + ' → ' + money(perCycle) + ' / ' + cycleNames[cycle];
                    } else {
                        rentHint.textContent = option && option.value ? @json(__('This unit has no rent set - enter the amount.')) : '';
                    }
                    applyTotal();
                }

                function applyEndDate() {
                    if (!startInput.value) return;
                    const cycle = cycleSelect.value || 'monthly';
                    const periods = Math.max(1, parseInt(periodsInput.value, 10) || 1);
                    const [y, m, d] = startInput.value.split('-').map(Number);
                    // Same as the server: add whole months (kept within the
                    // month's last day), then step back one day.
                    const target = new Date(Date.UTC(y, m - 1 + months[cycle] * periods, 1));
                    const lastDay = new Date(Date.UTC(target.getUTCFullYear(), target.getUTCMonth() + 1, 0)).getUTCDate();
                    target.setUTCDate(Math.min(d, lastDay));
                    target.setUTCDate(target.getUTCDate() - 1);
                    endInput.value = target.toISOString().slice(0, 10);
                    applyTotal();
                }

                // select2 drives the underlying <select> and fires jQuery events.
                $(unitSelect).on('change', applyRent);
                cycleSelect.addEventListener('change', () => { applyRent(); applyEndDate(); });
                startInput.addEventListener('change', applyEndDate);
                periodsInput.addEventListener('input', applyEndDate);
                rentInput.addEventListener('input', applyTotal);

                if (unitSelect.value) applyRent();
                if (startInput.value && !endInput.value) applyEndDate();
            });
        </script>
        @endpush
        <div class="col-md-3">
            <label class="form-label">{{ __('Billing Cycle') }}</label>
            <select name="billing_cycle" class="form-select @error('billing_cycle') is-invalid @enderror">
                @foreach (['monthly', 'quarterly', 'semi_annual', 'annual'] as $cycle)
                    <option value="{{ $cycle }}" @selected(old('billing_cycle', 'monthly') === $cycle)>{{ __(ucfirst(str_replace('_', ' ', $cycle))) }}</option>
                @endforeach
            </select>
            @error('billing_cycle') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">{{ __('Start Date') }}</label>
            <input type="date" name="start_date" value="{{ old('start_date') }}" class="form-control @error('start_date') is-invalid @enderror" required>
            @error('start_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">{{ __('Number of periods') }}</label>
            <input type="number" min="1" max="120" name="periods" value="{{ old('periods', 1) }}" class="form-control @error('periods') is-invalid @enderror">
            <div class="form-text">{{ __('e.g. 12 monthly periods = 1 year') }}</div>
            @error('periods') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">{{ __('End Date') }}</label>
            <input type="date" name="end_date" value="{{ old('end_date') }}" class="form-control @error('end_date') is-invalid @enderror" required>
            <div class="form-text">{{ __('Filled in from the start date - you can change it.') }}</div>
            @error('end_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    @else
        <div class="col-md-6">
            <label class="form-label">{{ __('Tenant') }}</label>
            <input type="text" value="{{ $lease->tenant->name }}" class="form-control" disabled>
        </div>
        <div class="col-md-6">
            <label class="form-label">{{ __('Unit') }}</label>
            <input type="text" value="{{ $lease->unit->unit_number }} · {{ $lease->unit->building->name }} · {{ $lease->unit->building->property->name }}" class="form-control" disabled>
        </div>
        <div class="col-md-4">
            <label class="form-label">{{ __('Start Date') }}</label>
            <input type="text" value="{{ $lease->start_date->toFormattedDateString() }}" class="form-control" disabled>
        </div>
        <div class="col-md-4">
            <label class="form-label">{{ __('End Date') }}</label>
            <input type="date" name="end_date" value="{{ old('end_date', $lease->end_date->toDateString()) }}" class="form-control @error('end_date') is-invalid @enderror" required>
            @error('end_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">{{ __('Billing Cycle') }}</label>
            <select name="billing_cycle" class="form-select @error('billing_cycle') is-invalid @enderror">
                @foreach (['monthly', 'quarterly', 'semi_annual', 'annual'] as $cycle)
                    <option value="{{ $cycle }}" @selected(old('billing_cycle', $lease->billing_cycle) === $cycle)>{{ __(ucfirst(str_replace('_', ' ', $cycle))) }}</option>
                @endforeach
            </select>
            @error('billing_cycle') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">{{ __('Status') }}</label>
            <select name="status" class="form-select @error('status') is-invalid @enderror">
                @foreach (['active', 'expired', 'terminated'] as $status)
                    <option value="{{ $status }}" @selected(old('status', $lease->status) === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-8">
            <label class="form-label">Termination Reason <span class="text-secondary fw-normal">(if terminating)</span></label>
            <input type="text" name="termination_reason" value="{{ old('termination_reason', $lease->termination_reason) }}" class="form-control @error('termination_reason') is-invalid @enderror">
            @error('termination_reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    @endif

    <div class="col-md-6">
        <label class="form-label">{{ __('Rent per billing period') }}</label>
        <input type="number" step="0.01" min="0" name="rent_amount" value="{{ old('rent_amount', $lease?->rent_amount ?? $lease?->unit->rent_amount) }}" class="form-control @error('rent_amount') is-invalid @enderror" required>
        @error('rent_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div id="rent-amount-hint" class="form-text"></div>
        <div id="lease-total-hint" class="form-text fw-semibold"></div>
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ __('Deposit Amount') }} <span class="text-secondary fw-normal">{{ __('(optional)') }}</span></label>
        <input type="number" step="0.01" min="0" name="deposit_amount" value="{{ old('deposit_amount', $lease?->deposit_amount) }}" class="form-control @error('deposit_amount') is-invalid @enderror">
        @error('deposit_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label class="form-label">{{ __('Notes') }}</label>
        <textarea name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $lease?->notes) }}</textarea>
        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>
