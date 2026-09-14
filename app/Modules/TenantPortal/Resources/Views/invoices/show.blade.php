@extends('tenant-portal::layout')

@section('title', 'Invoice ' . $invoice->invoice_number)

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 mb-0">Invoice {{ $invoice->invoice_number }}</h1>
        <a href="{{ route('portal.invoices.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> All Invoices
        </a>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h6 mb-3">Details</h2>
                    <div class="row g-3 small">
                        <div class="col-md-4">
                            <div class="text-secondary">Property</div>
                            <div>{{ $invoice->lease->unit->building->property->name }} &middot; {{ $invoice->lease->unit->unit_number }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-secondary">Issue Date</div>
                            <div>{{ $invoice->issue_date->toFormattedDateString() }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-secondary">Due Date</div>
                            <div>{{ $invoice->due_date->toFormattedDateString() }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-secondary">Total</div>
                            <div>{{ number_format($invoice->total_amount, 2) }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-secondary">Paid</div>
                            <div>{{ number_format($invoice->amount_paid, 2) }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-secondary">Balance Due</div>
                            <div class="fw-semibold">{{ number_format($invoice->balanceDue(), 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h6 mb-3">Line Items</h2>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($invoice->items as $item)
                                    <tr>
                                        <td>{{ $item->description }}</td>
                                        <td class="text-end">{{ number_format($item->amount, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            @if ($invoice->balanceDue() > 0 && in_array($invoice->status, ['sent', 'partially_paid', 'overdue']))
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h2 class="h6 mb-3">Pay Now</h2>
                        <form method="POST" action="{{ route('portal.invoices.pay', $invoice) }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label small">Amount</label>
                                <input type="number" step="0.01" min="0.01" max="{{ $invoice->balanceDue() }}" name="amount" value="{{ old('amount', $invoice->balanceDue()) }}" class="form-control @error('amount') is-invalid @enderror" required>
                                @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Pay Online</button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
