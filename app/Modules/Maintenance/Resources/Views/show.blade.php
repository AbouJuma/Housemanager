@extends('layouts.app')

@section('title', $maintenanceRequest->title)

@section('page-heading')
    <h1 class="h4 mb-0">{{ $maintenanceRequest->title }}</h1>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0 d-lg-none">{{ $maintenanceRequest->title }}</h1>
            <p class="text-secondary mb-0">
                <span class="badge text-bg-{{ match($maintenanceRequest->priority) { 'urgent' => 'danger', 'high' => 'warning', 'low' => 'secondary', default => 'info' } }} text-capitalize">{{ $maintenanceRequest->priority }}</span>
                <span class="badge text-bg-{{ match($maintenanceRequest->status) { 'completed' => 'success', 'cancelled' => 'secondary', 'in_progress' => 'primary', default => 'warning' } }} text-capitalize">{{ str_replace('_', ' ', $maintenanceRequest->status) }}</span>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('maintenance-requests.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> All Requests
            </a>
            @can('update', $maintenanceRequest)
                <a href="{{ route('maintenance-requests.edit', $maintenanceRequest) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-pencil me-1"></i> Edit
                </a>
            @endcan
            @can('delete', $maintenanceRequest)
                <form action="{{ route('maintenance-requests.destroy', $maintenanceRequest) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="button" class="btn btn-outline-danger btn-sm js-confirm-delete" data-name="this maintenance request">
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
                        <div class="col-md-4">
                            <div class="text-secondary">Property</div>
                            <div><a href="{{ route('properties.show', $maintenanceRequest->property) }}">{{ $maintenanceRequest->property->name }}</a></div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-secondary">Unit</div>
                            <div>
                                @if ($maintenanceRequest->unit)
                                    <a href="{{ route('units.show', $maintenanceRequest->unit) }}">{{ $maintenanceRequest->unit->unit_number }}</a>
                                @else
                                    —
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-secondary">Reported By</div>
                            <div>
                                @if ($maintenanceRequest->tenant)
                                    <a href="{{ route('tenants.show', $maintenanceRequest->tenant) }}">{{ $maintenanceRequest->tenant->name }}</a>
                                @else
                                    {{ $maintenanceRequest->reportedBy?->name ?? 'Staff' }}
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-secondary">Category</div>
                            <div class="text-capitalize">{{ str_replace('_', ' ', $maintenanceRequest->category) }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-secondary">Vendor</div>
                            <div>
                                @if ($maintenanceRequest->vendor)
                                    <a href="{{ route('vendors.show', $maintenanceRequest->vendor) }}">{{ $maintenanceRequest->vendor->name }}</a>
                                @else
                                    Unassigned
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-secondary">Reported Date</div>
                            <div>{{ $maintenanceRequest->reported_date->toFormattedDateString() }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-secondary">Scheduled Date</div>
                            <div>{{ $maintenanceRequest->scheduled_date?->toFormattedDateString() ?? '—' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-secondary">Completed Date</div>
                            <div>{{ $maintenanceRequest->completed_date?->toFormattedDateString() ?? '—' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-secondary">Estimated Cost</div>
                            <div>{{ $maintenanceRequest->estimated_cost !== null ? number_format($maintenanceRequest->estimated_cost, 2) : '—' }}</div>
                        </div>
                    </div>
                    @if ($maintenanceRequest->description)
                        <hr>
                        <div class="text-secondary small mb-1">Description</div>
                        <p class="mb-0">{{ $maintenanceRequest->description }}</p>
                    @endif
                    @if ($maintenanceRequest->notes)
                        <hr>
                        <div class="text-secondary small mb-1">Notes</div>
                        <p class="mb-0">{{ $maintenanceRequest->notes }}</p>
                    @endif
                </div>
            </div>

            @if ($inventoryTransactions->isNotEmpty())
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-body">
                        <h2 class="h6 mb-3">Parts Used</h2>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Quantity</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($inventoryTransactions as $transaction)
                                        <tr>
                                            <td class="fw-semibold"><a href="{{ route('inventory-items.show', $transaction->item) }}">{{ $transaction->item->name }}</a></td>
                                            <td>{{ rtrim(rtrim(number_format(abs($transaction->quantity), 2), '0'), '.') }} {{ $transaction->item->unit_of_measure }}</td>
                                            <td class="small text-secondary">{{ $transaction->transaction_date->toFormattedDateString() }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h6 mb-3">Cost</h2>

                    @if ($maintenanceRequest->expense)
                        <div class="text-secondary small">Recorded Expense</div>
                        <div class="mb-2">{{ number_format($maintenanceRequest->expense->amount, 2) }}</div>
                        <a href="{{ route('expenses.show', $maintenanceRequest->expense) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-eye me-1"></i> View Expense
                        </a>
                    @else
                        <p class="text-secondary small">No expense recorded against this request yet.</p>

                        @can('update', $maintenanceRequest)
                            <form method="POST" action="{{ route('maintenance-requests.record-expense', $maintenanceRequest) }}">
                                @csrf
                                <div class="mb-2">
                                    <label class="form-label small">Category</label>
                                    <select name="account_id" class="form-select form-select-sm @error('account_id') is-invalid @enderror" required>
                                        @foreach ($expenseAccounts as $account)
                                            <option value="{{ $account->id }}">{{ $account->code }} &middot; {{ $account->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small">Amount</label>
                                    <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount', $maintenanceRequest->estimated_cost) }}" class="form-control form-control-sm @error('amount') is-invalid @enderror" required>
                                    @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small">Expense Date</label>
                                    <input type="date" name="expense_date" value="{{ old('expense_date', now()->toDateString()) }}" class="form-control form-control-sm @error('expense_date') is-invalid @enderror" required>
                                    @error('expense_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small">Payment Status</label>
                                    <select name="payment_status" id="maintenancePaymentStatus" class="form-select form-select-sm @error('payment_status') is-invalid @enderror" required>
                                        <option value="unpaid">Unpaid (bill owed)</option>
                                        <option value="paid">Paid now</option>
                                    </select>
                                    @error('payment_status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="mb-2" id="maintenancePaymentMethodField">
                                    <label class="form-label small">Payment Method</label>
                                    <select name="payment_method" class="form-select form-select-sm @error('payment_method') is-invalid @enderror">
                                        @foreach (['cash', 'bank_transfer', 'cheque', 'mobile_money', 'other'] as $method)
                                            <option value="{{ $method }}">{{ ucfirst(str_replace('_', ' ', $method)) }}</option>
                                        @endforeach
                                    </select>
                                    @error('payment_method') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="mb-3" id="maintenancePaidOnField">
                                    <label class="form-label small">Paid On</label>
                                    <input type="date" name="paid_on" value="{{ old('paid_on', now()->toDateString()) }}" class="form-control form-control-sm @error('paid_on') is-invalid @enderror">
                                    @error('paid_on') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm">Record Expense</button>
                            </form>

                            <script>
                                document.addEventListener('DOMContentLoaded', () => {
                                    const statusSelect = document.getElementById('maintenancePaymentStatus');
                                    const methodField = document.getElementById('maintenancePaymentMethodField');
                                    const paidOnField = document.getElementById('maintenancePaidOnField');

                                    function toggle() {
                                        const paid = statusSelect.value === 'paid';
                                        methodField.classList.toggle('d-none', !paid);
                                        paidOnField.classList.toggle('d-none', !paid);
                                    }

                                    statusSelect.addEventListener('change', toggle);
                                    toggle();
                                });
                            </script>
                        @endcan
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
