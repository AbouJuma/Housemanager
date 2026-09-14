@extends('layouts.app')

@section('title', 'AR Aging')

@section('page-heading')
    <h1 class="h4 mb-0">AR Aging</h1>
@endsection

@section('content')
    @php($bucketLabels = ['current' => 'Current', '1_30' => '1-30 Days', '31_60' => '31-60 Days', '61_90' => '61-90 Days', 'over_90' => 'Over 90 Days'])

    <div class="d-flex justify-content-between align-items-center mb-3">
        <p class="text-secondary mb-0">Total outstanding: {{ number_format($grand_total, 2) }}</p>
        <div class="d-flex gap-2">
            <a href="{{ route('reports.ar-aging.export', 'pdf') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-file-earmark-pdf me-1"></i> PDF</a>
            <a href="{{ route('reports.ar-aging.export', 'xlsx') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-file-earmark-excel me-1"></i> Excel</a>
            <a href="{{ route('reports.ar-aging.export', 'csv') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-filetype-csv me-1"></i> CSV</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        @foreach ($bucketLabels as $key => $label)
            <div class="col-6 col-md-2-4" style="flex: 0 0 20%; max-width: 20%;">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-secondary small">{{ $label }}</div>
                        <div class="fs-5 fw-semibold">{{ number_format($totals[$key], 2) }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            @if (collect($buckets)->flatten(1)->isEmpty())
                <p class="text-secondary small mb-0">No outstanding invoices.</p>
            @else
                <div class="table-responsive">
                    <table id="arAgingTable" class="table table-striped table-hover table-sm align-middle datatable">
                        <thead>
                            <tr>
                                <th>Bucket</th>
                                <th>Invoice #</th>
                                <th>Tenant</th>
                                <th>Due Date</th>
                                <th class="text-end">Days Overdue</th>
                                <th class="text-end">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($buckets as $key => $invoices)
                                @foreach ($invoices as $invoice)
                                    <tr>
                                        <td><span class="badge text-bg-{{ $key === 'current' ? 'success' : ($key === 'over_90' ? 'danger' : 'warning') }}">{{ $bucketLabels[$key] }}</span></td>
                                        <td class="fw-semibold">{{ $invoice['invoice_number'] }}</td>
                                        <td>{{ $invoice['tenant'] }}</td>
                                        <td class="small">{{ $invoice['due_date'] }}</td>
                                        <td class="text-end">{{ $invoice['days_overdue'] }}</td>
                                        <td class="text-end">{{ number_format($invoice['balance'], 2) }}</td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
