@extends('layouts.app')

@section('title', $item->name)

@section('page-heading')
    <h1 class="h4 mb-0">{{ $item->name }}</h1>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0 d-lg-none">{{ $item->name }}</h1>
            <p class="text-secondary mb-0">
                <span class="badge text-bg-{{ $item->status === 'active' ? 'success' : 'secondary' }} text-capitalize">{{ $item->status }}</span>
                @if ($item->isLowStock())
                    <span class="badge text-bg-warning">Low Stock</span>
                @endif
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('inventory-items.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> All Items
            </a>
            @can('update', $item)
                <a href="{{ route('inventory-items.edit', $item) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-pencil me-1"></i> Edit
                </a>
            @endcan
            @can('delete', $item)
                <form action="{{ route('inventory-items.destroy', $item) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="button" class="btn btn-outline-danger btn-sm js-confirm-delete" data-name="this inventory item">
                        <i class="bi bi-trash me-1"></i> Delete
                    </button>
                </form>
            @endcan
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h6 mb-3">Details</h2>
                    <div class="row g-3 small">
                        <div class="col-md-3">
                            <div class="text-secondary">On Hand</div>
                            <div class="fw-semibold">{{ rtrim(rtrim(number_format($item->quantity_on_hand, 2), '0'), '.') }} {{ $item->unit_of_measure }}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-secondary">Reorder Level</div>
                            <div>{{ $item->reorder_level !== null ? rtrim(rtrim(number_format($item->reorder_level, 2), '0'), '.') : '—' }}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-secondary">Unit Cost</div>
                            <div>{{ $item->unit_cost !== null ? number_format($item->unit_cost, 2) : '—' }}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-secondary">Category</div>
                            <div class="text-capitalize">{{ str_replace('_', ' ', $item->category) }}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-secondary">SKU</div>
                            <div>{{ $item->sku ?? '—' }}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-secondary">Property</div>
                            <div>
                                @if ($item->property)
                                    <a href="{{ route('properties.show', $item->property) }}">{{ $item->property->name }}</a>
                                @else
                                    Company-wide
                                @endif
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-secondary">Preferred Vendor</div>
                            <div>
                                @if ($item->vendor)
                                    <a href="{{ route('vendors.show', $item->vendor) }}">{{ $item->vendor->name }}</a>
                                @else
                                    —
                                @endif
                            </div>
                        </div>
                    </div>
                    @if ($item->notes)
                        <hr>
                        <div class="text-secondary small mb-1">Notes</div>
                        <p class="mb-0">{{ $item->notes }}</p>
                    @endif
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h6 mb-3">Transaction History</h2>

                    @if ($item->transactions->isEmpty())
                        <p class="text-secondary small mb-0">No stock movements recorded yet.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Quantity</th>
                                        <th>Balance After</th>
                                        <th>Linked To</th>
                                        <th>Recorded By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($item->transactions as $transaction)
                                        <tr>
                                            <td class="small">{{ $transaction->transaction_date->toFormattedDateString() }}</td>
                                            <td>
                                                <span class="badge text-bg-{{ match($transaction->type) { 'receipt' => 'success', 'usage' => 'primary', default => 'secondary' } }} text-capitalize">{{ $transaction->type }}</span>
                                            </td>
                                            <td class="{{ $transaction->quantity < 0 ? 'text-danger' : 'text-success' }}">{{ $transaction->quantity > 0 ? '+' : '' }}{{ rtrim(rtrim(number_format($transaction->quantity, 2), '0'), '.') }}</td>
                                            <td>{{ rtrim(rtrim(number_format($transaction->quantity_after, 2), '0'), '.') }}</td>
                                            <td class="small text-secondary">
                                                @if ($transaction->maintenanceRequest)
                                                    <a href="{{ route('maintenance-requests.show', $transaction->maintenanceRequest) }}">{{ $transaction->maintenanceRequest->title }}</a>
                                                @elseif ($transaction->reason)
                                                    {{ $transaction->reason }}
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td class="small text-secondary">{{ $transaction->recordedBy?->name ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            @can('update', $item)
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h2 class="h6 mb-3">Record Transaction</h2>

                        <form method="POST" action="{{ route('inventory-items.record-transaction', $item) }}">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label small">Type</label>
                                <select name="type" id="inventoryTxnType" class="form-select form-select-sm @error('type') is-invalid @enderror" required>
                                    <option value="receipt">Receipt (stock coming in)</option>
                                    <option value="usage">Usage (stock consumed)</option>
                                    <option value="adjustment">Adjustment (correction)</option>
                                </select>
                                @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">Quantity</label>
                                <input type="number" step="0.01" min="0.01" name="quantity" class="form-control form-control-sm @error('quantity') is-invalid @enderror" required>
                                @error('quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="mb-2" id="inventoryTxnDirectionField">
                                <label class="form-label small">Direction</label>
                                <select name="direction" class="form-select form-select-sm @error('direction') is-invalid @enderror">
                                    <option value="increase">Increase</option>
                                    <option value="decrease">Decrease</option>
                                </select>
                                @error('direction') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="mb-2" id="inventoryTxnReasonField">
                                <label class="form-label small">Reason</label>
                                <textarea name="reason" rows="2" class="form-control form-control-sm @error('reason') is-invalid @enderror"></textarea>
                                @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="mb-2" id="inventoryTxnMaintenanceField">
                                <label class="form-label small">Used On <span class="text-secondary fw-normal">(optional)</span></label>
                                <select name="maintenance_request_id" class="form-select form-select-sm @error('maintenance_request_id') is-invalid @enderror">
                                    <option value="">&mdash; Not tied to a request &mdash;</option>
                                    @foreach ($maintenanceRequests as $request)
                                        <option value="{{ $request->id }}">{{ $request->title }}</option>
                                    @endforeach
                                </select>
                                @error('maintenance_request_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label small">Date</label>
                                <input type="date" name="transaction_date" value="{{ old('transaction_date', now()->toDateString()) }}" class="form-control form-control-sm @error('transaction_date') is-invalid @enderror" required>
                                @error('transaction_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">Record</button>
                        </form>

                        <script>
                            document.addEventListener('DOMContentLoaded', () => {
                                const typeSelect = document.getElementById('inventoryTxnType');
                                const directionField = document.getElementById('inventoryTxnDirectionField');
                                const reasonField = document.getElementById('inventoryTxnReasonField');
                                const maintenanceField = document.getElementById('inventoryTxnMaintenanceField');

                                function toggle() {
                                    const type = typeSelect.value;
                                    directionField.classList.toggle('d-none', type !== 'adjustment');
                                    reasonField.classList.toggle('d-none', type !== 'adjustment');
                                    maintenanceField.classList.toggle('d-none', type !== 'usage');
                                }

                                typeSelect.addEventListener('change', toggle);
                                toggle();
                            });
                        </script>
                    </div>
                </div>
            @endcan
        </div>
    </div>
@endsection
