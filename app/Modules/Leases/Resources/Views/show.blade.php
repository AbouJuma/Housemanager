@extends('layouts.app')

@section('title', 'Lease · ' . $lease->tenant->name)

@section('page-heading')
    <h1 class="h4 mb-0">Lease &middot; {{ $lease->tenant->name }}</h1>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0 d-lg-none">Lease &middot; {{ $lease->tenant->name }}</h1>
            <p class="text-secondary mb-0 text-capitalize">{{ $lease->status }} &middot; {{ str_replace('_', ' ', $lease->billing_cycle) }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('leases.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> All Leases
            </a>
            @can('update', $lease)
                <a href="{{ route('leases.edit', $lease) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-pencil me-1"></i> Edit
                </a>
            @endcan
        </div>
    </div>

    @if ($lease->status === 'terminated')
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle me-1"></i> This lease was terminated on {{ $lease->terminated_at?->format('M j, Y') }}.
            @if ($lease->termination_reason)
                Reason: {{ $lease->termination_reason }}
            @endif
        </div>
    @elseif ($lease->status === 'expired')
        <div class="alert alert-secondary">
            <i class="bi bi-clock-history me-1"></i> This lease expired on {{ $lease->end_date->format('M j, Y') }}.
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-8">
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
                    @if ($lease->notes)
                        <hr>
                        <div class="text-secondary small mb-1">Notes</div>
                        <p class="mb-0">{{ $lease->notes }}</p>
                    @endif
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h6 mb-3">Tenant</h2>
                    <p class="mb-1 fw-semibold">{{ $lease->tenant->name }}</p>
                    <a href="{{ route('tenants.show', $lease->tenant) }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-eye me-1"></i> View Tenant
                    </a>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h6 mb-0">Invoices</h2>
                        @can('create', \App\Modules\Invoices\Models\Invoice::class)
                            <a href="{{ route('invoices.create', ['lease_id' => $lease->uuid]) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-plus-lg me-1"></i> New Invoice
                            </a>
                        @endcan
                    </div>

                    @if ($invoices->isEmpty())
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
                                    @foreach ($invoices as $invoice)
                                        <tr>
                                            <td class="fw-semibold">{{ $invoice->invoice_number }}</td>
                                            <td class="small">{{ $invoice->due_date->format('M j, Y') }}</td>
                                            <td>{{ number_format($invoice->total_amount, 2) }}</td>
                                            <td><span class="badge text-bg-secondary text-capitalize">{{ str_replace('_', ' ', $invoice->status) }}</span></td>
                                            <td class="text-end">
                                                <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
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
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h6 mb-3">Unit</h2>
                    <p class="mb-1 fw-semibold">{{ $lease->unit->unit_number }}</p>
                    <p class="text-secondary small mb-3">
                        {{ $lease->unit->building->name }}<br>
                        {{ $lease->unit->building->property->name }}
                    </p>
                    <a href="{{ route('units.show', $lease->unit) }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-eye me-1"></i> View Unit
                    </a>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h6 mb-0">Documents</h2>
                        @can('create', \App\Modules\Documents\Models\Document::class)
                            <a href="{{ route('documents.create', ['documentable_type' => 'lease', 'documentable_id' => $lease->id]) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-plus-lg me-1"></i> Add Document
                            </a>
                        @endcan
                    </div>

                    @if ($documents->isEmpty())
                        <p class="text-secondary small mb-0">No documents attached to this lease yet.</p>
                    @else
                        <ul class="list-unstyled mb-0">
                            @foreach ($documents as $item)
                                <li class="d-flex justify-content-between align-items-center py-1">
                                    <a href="{{ route('documents.show', $item) }}" class="small">{{ $item->title }}</a>
                                    <span class="badge text-bg-secondary text-capitalize">{{ str_replace('_', ' ', $item->category) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
