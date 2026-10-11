@php($invoice = $invoice ?? null)
@php($items = old('items', $invoice?->items->map(fn ($item) => ['description' => $item->description, 'quantity' => (float) $item->quantity, 'unit_price' => (float) $item->unit_price])->all() ?? [['description' => '', 'quantity' => 1, 'unit_price' => '']]))

<div class="row g-3">
    @if (! $invoice)
        <div class="col-md-6">
            <label class="form-label">{{ __('Lease') }}</label>
            <select name="lease_id" class="form-select @error('lease_id') is-invalid @enderror" required>
                <option value="">&mdash; Select a lease &mdash;</option>
                @foreach ($leases as $lease)
                    <option value="{{ $lease->id }}" @selected((int) old('lease_id') === $lease->id || (! old('lease_id') && $lease->uuid === $selectedLeaseUuid))>
                        {{ $lease->tenant->name }} &middot; {{ $lease->unit->unit_number }} &middot; {{ ucfirst($lease->status) }}
                    </option>
                @endforeach
            </select>
            @error('lease_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    @else
        <div class="col-md-6">
            <label class="form-label">{{ __('Lease') }}</label>
            <input type="text" value="{{ $invoice->lease->tenant->name }} · {{ $invoice->lease->unit->unit_number }}" class="form-control" disabled>
        </div>
    @endif

    <div class="col-md-3">
        <label class="form-label">{{ __('Issue Date') }}</label>
        <input type="date" name="issue_date" value="{{ old('issue_date', $invoice?->issue_date?->toDateString() ?? now()->toDateString()) }}" class="form-control @error('issue_date') is-invalid @enderror" required>
        @error('issue_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">{{ __('Due Date') }}</label>
        <input type="date" name="due_date" value="{{ old('due_date', $invoice?->due_date?->toDateString() ?? now()->addDays(14)->toDateString()) }}" class="form-control @error('due_date') is-invalid @enderror" required>
        @error('due_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <hr>
        <h2 class="h6 mb-3">{{ __('Line Items') }}</h2>
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead>
                    <tr>
                        <th>{{ __('Description') }}</th>
                        <th style="width: 110px;">{{ __('Qty') }}</th>
                        <th style="width: 140px;">{{ __('Unit Price') }}</th>
                        <th style="width: 120px;">{{ __('Amount') }}</th>
                        <th style="width: 40px;"></th>
                    </tr>
                </thead>
                <tbody id="invoiceItemsBody">
                    @foreach ($items as $i => $item)
                        <tr class="invoice-item-row">
                            <td><input type="text" name="items[{{ $i }}][description]" value="{{ $item['description'] }}" class="form-control form-control-sm" required></td>
                            <td><input type="number" step="0.01" min="0.01" name="items[{{ $i }}][quantity]" value="{{ $item['quantity'] }}" class="form-control form-control-sm js-item-qty" required></td>
                            <td><input type="number" step="0.01" min="0" name="items[{{ $i }}][unit_price]" value="{{ $item['unit_price'] }}" class="form-control form-control-sm js-item-price" required></td>
                            <td class="js-item-amount text-end">0.00</td>
                            <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger js-remove-item"><i class="bi bi-trash"></i></button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @error('items') <div class="text-danger small mb-2">{{ $message }}</div> @enderror
        <button type="button" id="addInvoiceItemBtn" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-plus-lg me-1"></i> {{ __('Add Item') }}
        </button>
        <div class="text-end fw-semibold mt-2">Total: <span id="invoiceItemsTotal">0.00</span></div>
    </div>

    <div class="col-12">
        <label class="form-label">{{ __('Notes') }}</label>
        <textarea name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $invoice?->notes) }}</textarea>
        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<template id="invoiceItemRowTemplate">
    <tr class="invoice-item-row">
        <td><input type="text" name="items[__INDEX__][description]" class="form-control form-control-sm" required></td>
        <td><input type="number" step="0.01" min="0.01" name="items[__INDEX__][quantity]" value="1" class="form-control form-control-sm js-item-qty" required></td>
        <td><input type="number" step="0.01" min="0" name="items[__INDEX__][unit_price]" class="form-control form-control-sm js-item-price" required></td>
        <td class="js-item-amount text-end">0.00</td>
        <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger js-remove-item"><i class="bi bi-trash"></i></button></td>
    </tr>
</template>
