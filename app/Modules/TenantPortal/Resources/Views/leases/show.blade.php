@extends('tenant-portal::layout')

@section('title', 'Lease · ' . $lease->unit->unit_number)

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 mb-0">{{ $lease->unit->unit_number }} &middot; {{ $lease->unit->building->property->name }}</h1>
        <a href="{{ route('portal.leases.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> All Leases
        </a>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h6 mb-3">Lease Terms</h2>
            <div class="row g-3 small">
                <div class="col-md-4">
                    <div class="text-secondary">Start Date</div>
                    <div>{{ $lease->start_date->toFormattedDateString() }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-secondary">End Date</div>
                    <div>{{ $lease->end_date->toFormattedDateString() }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-secondary">Status</div>
                    <div class="text-capitalize">{{ $lease->status }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-secondary">Billing Cycle</div>
                    <div class="text-capitalize">{{ str_replace('_', ' ', $lease->billing_cycle) }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-secondary">Rent Amount</div>
                    <div>{{ number_format($lease->rent_amount, 2) }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-secondary">Deposit Amount</div>
                    <div>{{ $lease->deposit_amount !== null ? number_format($lease->deposit_amount, 2) : '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h2 class="h6 mb-3">Invoices</h2>
            @if ($lease->invoices->isEmpty())
                <p class="text-secondary small mb-0">No invoices yet.</p>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Number</th>
                                <th>Due</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lease->invoices as $invoice)
                                <tr>
                                    <td class="fw-semibold">{{ $invoice->invoice_number }}</td>
                                    <td class="small">{{ $invoice->due_date->format('M j, Y') }}</td>
                                    <td>{{ number_format($invoice->total_amount, 2) }}</td>
                                    <td><span class="badge text-bg-secondary text-capitalize">{{ str_replace('_', ' ', $invoice->status) }}</span></td>
                                    <td class="text-end">
                                        <a href="{{ route('portal.invoices.show', $invoice) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
