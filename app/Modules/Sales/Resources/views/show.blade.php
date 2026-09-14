@extends('layouts.app')

@section('title', 'Sale #' . $sale->id)

@section('page-heading')
    <h1 class="h4 mb-0">Sale #{{ $sale->id }}</h1>
@endsection

@section('content')
    <x-breadcrumb :items="collect([
        ['label' => 'Sales', 'url' => route('sales.index')],
        ['label' => 'Sale #' . $sale->id],
    ])" />

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0 d-lg-none">Sale #{{ $sale->id }}</h1>
        </div>
        <div class="d-flex gap-2">
            @can('update', $sale)
                <a href="{{ route('sales.edit', $sale) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-pencil me-1"></i> Edit
                </a>
            @endcan
            @can('delete', $sale)
                <form action="{{ route('sales.destroy', $sale) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="button" class="btn btn-outline-danger btn-sm js-confirm-delete" data-name="Sale #{{ $sale->id }}">
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
                    <h2 class="h6 mb-3">Sale Details</h2>
                    <div class="row g-3 small">
                        <div class="col-md-6">
                            <div class="text-secondary">Unit</div>
                            <div class="fw-semibold">{{ $sale->unit->unit_number }} · {{ $sale->unit->building->name }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-secondary">Buyer</div>
                            <div>
                                @if ($sale->new_buyer_id)
                                    {{ $sale->buyer->name ?? '—' }}
                                @else
                                    &mdash;
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-secondary">Sale Date</div>
                            <div>{{ $sale->sale_date->format('M j, Y') }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-secondary">Total Price</div>
                            <div class="fw-semibold">{{ number_format($sale->total_price, 2) }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-secondary">Down Payment</div>
                            <div>{{ number_format($sale->down_payment, 2) }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-secondary">Installment Period</div>
                            <div>{{ $sale->installment_period }} months</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-secondary">Installment Amount</div>
                            <div>{{ number_format($sale->installment_amount, 2) }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-secondary">Status</div>
                            <div>
                                <span class="badge text-bg-{{ $sale->status === 'pending' ? 'secondary' : ($sale->status === 'active' ? 'primary' : ($sale->status === 'completed' ? 'success' : ($sale->status === 'cancelled' ? 'danger' : 'secondary'))) }}">{{ ucfirst($sale->status) }}</span>
                            </div>
                        </div>
                    </div>
                    @if ($sale->notes)
                        <p class="mt-3 mb-0">{{ $sale->notes }}</p>
                    @endif
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h6 mb-3">Payment Progress</h2>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span>{{ number_format($sale->total_paid, 2) }} paid of {{ number_format($sale->total_price, 2) }}</span>
                            <span>{{ $sale->completion_percentage }}%</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $sale->completion_percentage }}%"></div>
                        </div>
                    </div>
                    <div class="row g-3 small">
                        <div class="col-md-4">
                            <div class="text-secondary">Total Paid</div>
                            <div class="fw-semibold">{{ number_format($sale->total_paid, 2) }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-secondary">Remaining Balance</div>
                            <div class="fw-semibold">{{ number_format($sale->remaining_balance, 2) }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-secondary">Installments Paid</div>
                            <div>{{ $sale->paidInstallments()->count() }} / {{ $sale->installments()->count() }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h6 mb-3">Installment Schedule</h2>
                    @if ($sale->installments->isEmpty())
                        <p class="text-secondary small mb-0">No installments scheduled.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle small">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Due Date</th>
                                        <th>Amount</th>
                                        <th>Paid</th>
                                        <th>Status</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($sale->installments as $installment)
                                        <tr>
                                            <td>{{ $installment->installment_number }}</td>
                                            <td>{{ $installment->due_date->format('M j, Y') }}</td>
                                            <td>{{ number_format($installment->amount, 2) }}</td>
                                            <td>{{ number_format($installment->paid_amount, 2) }}</td>
                                            <td>
                                                <span class="badge text-bg-{{ $installment->status === 'pending' ? 'secondary' : ($installment->status === 'paid' ? 'success' : ($installment->status === 'overdue' ? 'danger' : ($installment->status === 'partially_paid' ? 'warning' : 'secondary'))) }}">{{ ucfirst(str_replace('_', ' ', $installment->status)) }}</span>
                                            </td>
                                            <td class="text-end">
                                                @if (!$installment->isPaid())
                                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#paymentModal" data-installment-id="{{ $installment->uuid }}" data-amount="{{ $installment->remaining_amount }}">
                                                        <i class="bi bi-cash"></i> Pay
                                                    </button>
                                                @endif
                                            </td>
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
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h6 mb-3">Recorded By</h2>
                    <div class="text-secondary">
                        @if ($sale->recordedBy)
                            {{ $sale->recordedBy->name }}
                        @else
                            &mdash;
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Record Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="paymentForm" method="POST" action="">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Amount to Pay</label>
                            <input type="number" step="0.01" min="0.01" name="amount" id="paymentAmount" class="form-control" required>
                            <small class="text-secondary">Remaining: <span id="remainingAmount"></span></small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submitPayment">Record Payment</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const paymentModal = document.getElementById('paymentModal');
            const paymentForm = document.getElementById('paymentForm');
            const submitButton = document.getElementById('submitPayment');

            paymentModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const installmentUuid = button.dataset.installmentId;
                const remainingAmount = parseFloat(button.dataset.amount);

                document.getElementById('paymentAmount').value = remainingAmount.toFixed(2);
                document.getElementById('remainingAmount').textContent = remainingAmount.toFixed(2);
                const baseUrl = '{{ route('sales.installments.payment', [$sale, 'INSTALLMENT_UUID']) }}';
                paymentForm.action = baseUrl.replace('INSTALLMENT_UUID', installmentUuid);
            });

            submitButton.addEventListener('click', function() {
                paymentForm.submit();
            });
        });
    </script>
    @endpush
@endsection
