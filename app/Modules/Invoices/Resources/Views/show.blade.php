@extends('layouts.app')

@section('title', $invoice->invoice_number)

@section('page-heading')
    <h1 class="h4 mb-0">{{ $invoice->invoice_number }}</h1>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0 d-lg-none">{{ $invoice->invoice_number }}</h1>
            <p class="text-secondary mb-0">
                <span class="badge text-bg-{{ match($invoice->status) { 'paid' => 'success', 'overdue' => 'danger', 'cancelled' => 'secondary', 'partially_paid' => 'warning', default => 'info' } }} text-capitalize">{{ str_replace('_', ' ', $invoice->status) }}</span>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> All Invoices
            </a>
            <a href="{{ route('invoices.pdf', $invoice) }}" class="btn btn-outline-primary btn-sm" target="_blank">
                <i class="bi bi-file-earmark-pdf me-1"></i> Download PDF
            </a>
            @can('update', $invoice)
                @if ($invoice->isEditable())
                    <a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-pencil me-1"></i> Edit
                    </a>
                    <form action="{{ route('invoices.send', $invoice) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-send me-1"></i> Send Invoice</button>
                    </form>
                @endif
                @if (! in_array($invoice->status, ['paid', 'cancelled']))
                    <form action="{{ route('invoices.cancel', $invoice) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="button" class="btn btn-outline-danger btn-sm js-confirm-delete" data-name="this invoice" data-text="Cancelling this invoice cannot be undone.">
                            <i class="bi bi-x-lg me-1"></i> Cancel
                        </button>
                    </form>
                @endif
            @endcan
            @can('delete', $invoice)
                @if (in_array($invoice->status, ['draft', 'cancelled']))
                    <form action="{{ route('invoices.destroy', $invoice) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="button" class="btn btn-outline-danger btn-sm js-confirm-delete" data-name="{{ $invoice->invoice_number }}">
                            <i class="bi bi-trash me-1"></i> Delete
                        </button>
                    </form>
                @endif
            @endcan
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h6 mb-3">Details</h2>
                    <div class="row g-3 small">
                        @if($invoice->lease)
                            <div class="col-md-4">
                                <div class="text-secondary">Tenant</div>
                                <div><a href="{{ route('tenants.show', $invoice->lease->tenant) }}">{{ $invoice->lease->tenant->name }}</a></div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-secondary">Unit</div>
                                <div><a href="{{ route('units.show', $invoice->lease->unit) }}">{{ $invoice->lease->unit->unit_number }}</a> &middot; {{ $invoice->lease->unit->building->property->name }}</div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-secondary">Lease</div>
                                <div><a href="{{ route('leases.show', $invoice->lease) }}">View Lease</a></div>
                            </div>
                        @elseif($invoice->sale)
                            <div class="col-md-4">
                                <div class="text-secondary">Buyer</div>
                                <div><a href="{{ route('buyers.show', $invoice->sale->buyer) }}">{{ $invoice->sale->buyer->name }}</a></div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-secondary">Unit</div>
                                <div><a href="{{ route('units.show', $invoice->sale->unit) }}">{{ $invoice->sale->unit->unit_number }}</a> &middot; {{ $invoice->sale->unit->building->property->name }}</div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-secondary">Sale</div>
                                <div><a href="{{ route('sales.show', $invoice->sale) }}">View Sale</a></div>
                            </div>
                        @endif
                        <div class="col-md-4">
                            <div class="text-secondary">Issue Date</div>
                            <div>{{ $invoice->issue_date->toFormattedDateString() }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-secondary">Due Date</div>
                            <div>{{ $invoice->due_date->toFormattedDateString() }}</div>
                        </div>
                    </div>
                    @if ($invoice->notes)
                        <hr>
                        <div class="text-secondary small mb-1">Notes</div>
                        <p class="mb-0">{{ $invoice->notes }}</p>
                    @endif
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h6 mb-3">Line Items</h2>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th class="text-end">Qty</th>
                                    <th class="text-end">Unit Price</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($invoice->items as $item)
                                    <tr>
                                        <td>{{ $item->description }}</td>
                                        <td class="text-end">{{ rtrim(rtrim(number_format($item->quantity, 2), '0'), '.') }}</td>
                                        <td class="text-end">{{ number_format($item->unit_price, 2) }}</td>
                                        <td class="text-end">{{ number_format($item->amount, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3" class="text-end">Total</th>
                                    <th class="text-end">{{ number_format($invoice->total_amount, 2) }}</th>
                                </tr>
                                <tr>
                                    <th colspan="3" class="text-end">Paid</th>
                                    <th class="text-end">{{ number_format($invoice->amount_paid, 2) }}</th>
                                </tr>
                                <tr>
                                    <th colspan="3" class="text-end">Balance Due</th>
                                    <th class="text-end">{{ number_format($invoice->balanceDue(), 2) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h6 mb-3">Payments</h2>

                    @if ($invoice->payments->isEmpty())
                        <p class="text-secondary small mb-0">No payments recorded yet.</p>
                    @else
                        <div class="table-responsive mb-3">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Method</th>
                                        <th>Reference</th>
                                        <th class="text-end">Amount</th>
                                        <th>Recorded By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($invoice->payments as $payment)
                                        <tr>
                                            <td class="small">{{ $payment->paid_on->format('M j, Y') }}</td>
                                            <td class="text-capitalize">{{ str_replace('_', ' ', $payment->method) }}</td>
                                            <td>{{ $payment->reference ?? '—' }}</td>
                                            <td class="text-end">{{ number_format($payment->amount, 2) }}</td>
                                            <td class="small text-secondary">{{ $payment->recordedBy?->name ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    @can('update', $invoice)
                        @if (in_array($invoice->status, ['sent', 'partially_paid', 'overdue']))
                            <hr>
                            <h3 class="h6 mb-3">Record a Payment</h3>
                            <form method="POST" action="{{ route('invoices.payments.store', $invoice) }}">
                                @csrf
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label">Amount</label>
                                        <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" class="form-control @error('amount') is-invalid @enderror" required>
                                        @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Paid On</label>
                                        <input type="date" name="paid_on" value="{{ old('paid_on', now()->toDateString()) }}" class="form-control @error('paid_on') is-invalid @enderror" required>
                                        @error('paid_on') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Method</label>
                                        <select name="method" class="form-select @error('method') is-invalid @enderror">
                                            @foreach (['cash', 'bank_transfer', 'mobile_money', 'cheque', 'other'] as $method)
                                                <option value="{{ $method }}" @selected(old('method') === $method)>{{ ucfirst(str_replace('_', ' ', $method)) }}</option>
                                            @endforeach
                                        </select>
                                        @error('method') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Reference <span class="text-secondary fw-normal">(optional)</span></label>
                                        <input type="text" name="reference" value="{{ old('reference') }}" class="form-control @error('reference') is-invalid @enderror">
                                        @error('reference') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Notes <span class="text-secondary fw-normal">(optional)</span></label>
                                        <input type="text" name="notes" value="{{ old('notes') }}" class="form-control @error('notes') is-invalid @enderror">
                                        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm mt-3">Record Payment</button>
                            </form>
                        @endif
                    @endcan
                </div>
            </div>

            @if ($invoice->paymentAttempts->isNotEmpty() || ($hasActivePaymentProvider && in_array($invoice->status, ['sent', 'partially_paid', 'overdue'])))
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-body">
                        <h2 class="h6 mb-3">Online Payment</h2>

                        @if ($invoice->paymentAttempts->isNotEmpty())
                            <div class="table-responsive mb-3">
                                <table class="table align-middle">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Provider</th>
                                            <th class="text-end">Amount</th>
                                            <th>Status</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($invoice->paymentAttempts->sortByDesc('created_at') as $attempt)
                                            <tr>
                                                <td class="small">{{ $attempt->created_at->format('M j, Y g:ia') }}</td>
                                                <td class="text-capitalize">{{ $attempt->provider }}</td>
                                                <td class="text-end">{{ number_format($attempt->amountMajor(), 2) }}</td>
                                                <td>
                                                    <span class="badge text-bg-{{ match($attempt->status) { 'successful' => 'success', 'failed' => 'danger', default => 'warning' } }} text-capitalize">{{ $attempt->status }}</span>
                                                </td>
                                                <td class="text-end">
                                                    @can('update', $invoice)
                                                        @if ($attempt->isPending())
                                                            <form action="{{ route('invoices.payment-attempts.verify', [$invoice, $attempt]) }}" method="POST" class="d-inline">
                                                                @csrf
                                                                <button type="submit" class="btn btn-sm btn-outline-secondary">Check Status</button>
                                                            </form>
                                                        @endif
                                                    @endcan
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        @can('update', $invoice)
                            @if ($hasActivePaymentProvider && in_array($invoice->status, ['sent', 'partially_paid', 'overdue']))
                                <form method="POST" action="{{ route('invoices.charge', $invoice) }}">
                                    @csrf
                                    <div class="row g-3 align-items-end">
                                        <div class="col-md-4">
                                            <label class="form-label">Amount to Charge</label>
                                            <input type="number" step="0.01" min="0.01" max="{{ $invoice->balanceDue() }}" name="amount" value="{{ old('amount', $invoice->balanceDue()) }}" class="form-control @error('amount') is-invalid @enderror" required>
                                            @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-auto">
                                            <button type="submit" class="btn btn-primary btn-sm">Charge Online</button>
                                        </div>
                                    </div>
                                </form>
                            @endif
                        @endcan
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
