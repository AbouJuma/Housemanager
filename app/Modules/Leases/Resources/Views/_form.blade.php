@php($lease = $lease ?? null)

<div class="row g-3">
    @if (! $lease)
        <div class="col-md-6">
            <label class="form-label">Tenant</label>
            <select name="tenant_id" id="tenant-select" class="form-select @error('tenant_id') is-invalid @enderror" required>
                <option value="">&mdash; Select a tenant &mdash;</option>
                @foreach ($tenants as $tenant)
                    <option value="{{ $tenant->id }}" @selected((int) old('tenant_id') === $tenant->id)>{{ $tenant->name }}</option>
                @endforeach
            </select>
            <div class="mt-1">
                <button type="button" class="btn btn-sm btn-link text-primary p-0" data-bs-toggle="modal" data-bs-target="#addTenantModal">
                    <i class="bi bi-plus-circle"></i> Add new tenant
                </button>
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
            });
        </script>
        @endpush
        <div class="col-md-6">
            <label class="form-label">Unit</label>
            <select name="unit_id" class="form-select @error('unit_id') is-invalid @enderror" required>
                <option value="">&mdash; Select a vacant unit &mdash;</option>
                @foreach ($units as $unit)
                    <option value="{{ $unit->id }}" @selected((int) old('unit_id') === $unit->id || (! old('unit_id') && $unit->uuid === $selectedUnitUuid))>
                        {{ $unit->unit_number }} &middot; {{ $unit->building->name }} &middot; {{ $unit->building->property->name }}
                    </option>
                @endforeach
            </select>
            @error('unit_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            @if ($units->isEmpty())
                <div class="form-text text-warning">No vacant units available right now.</div>
            @endif
        </div>
        <div class="col-md-4">
            <label class="form-label">Start Date</label>
            <input type="date" name="start_date" value="{{ old('start_date') }}" class="form-control @error('start_date') is-invalid @enderror" required>
            @error('start_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">End Date</label>
            <input type="date" name="end_date" value="{{ old('end_date') }}" class="form-control @error('end_date') is-invalid @enderror" required>
            @error('end_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Billing Cycle</label>
            <select name="billing_cycle" class="form-select @error('billing_cycle') is-invalid @enderror">
                @foreach (['monthly', 'quarterly', 'semi_annual', 'annual'] as $cycle)
                    <option value="{{ $cycle }}" @selected(old('billing_cycle', 'monthly') === $cycle)>{{ ucfirst(str_replace('_', ' ', $cycle)) }}</option>
                @endforeach
            </select>
            @error('billing_cycle') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    @else
        <div class="col-md-6">
            <label class="form-label">Tenant</label>
            <input type="text" value="{{ $lease->tenant->name }}" class="form-control" disabled>
        </div>
        <div class="col-md-6">
            <label class="form-label">Unit</label>
            <input type="text" value="{{ $lease->unit->unit_number }} · {{ $lease->unit->building->name }} · {{ $lease->unit->building->property->name }}" class="form-control" disabled>
        </div>
        <div class="col-md-4">
            <label class="form-label">Start Date</label>
            <input type="text" value="{{ $lease->start_date->toFormattedDateString() }}" class="form-control" disabled>
        </div>
        <div class="col-md-4">
            <label class="form-label">End Date</label>
            <input type="date" name="end_date" value="{{ old('end_date', $lease->end_date->toDateString()) }}" class="form-control @error('end_date') is-invalid @enderror" required>
            @error('end_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Billing Cycle</label>
            <select name="billing_cycle" class="form-select @error('billing_cycle') is-invalid @enderror">
                @foreach (['monthly', 'quarterly', 'semi_annual', 'annual'] as $cycle)
                    <option value="{{ $cycle }}" @selected(old('billing_cycle', $lease->billing_cycle) === $cycle)>{{ ucfirst(str_replace('_', ' ', $cycle)) }}</option>
                @endforeach
            </select>
            @error('billing_cycle') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Status</label>
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
        <label class="form-label">Rent Amount</label>
        <input type="number" step="0.01" min="0" name="rent_amount" value="{{ old('rent_amount', $lease?->rent_amount ?? $lease?->unit->rent_amount) }}" class="form-control @error('rent_amount') is-invalid @enderror" required>
        @error('rent_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Deposit Amount <span class="text-secondary fw-normal">(optional)</span></label>
        <input type="number" step="0.01" min="0" name="deposit_amount" value="{{ old('deposit_amount', $lease?->deposit_amount) }}" class="form-control @error('deposit_amount') is-invalid @enderror">
        @error('deposit_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label class="form-label">Notes</label>
        <textarea name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $lease?->notes) }}</textarea>
        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>
