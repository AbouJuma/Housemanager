@extends('tenant-portal::layout')

@section('title', 'Dashboard')

@section('content')
    <h1 class="h4 mb-4">Welcome, {{ $tenant->name }}</h1>

    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 text-secondary mb-2">Outstanding Balance</h2>
                    <p class="display-6 mb-0">{{ number_format($outstandingBalance, 2) }}</p>
                    <p class="text-secondary small mb-0">{{ $outstandingInvoices->count() }} unpaid {{ Str::plural('invoice', $outstandingInvoices->count()) }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 text-secondary mb-2">Active Lease{{ $activeLeases->count() === 1 ? '' : 's' }}</h2>
                    @if ($activeLeases->isEmpty())
                        <p class="text-secondary small mb-0">No active lease on file.</p>
                    @else
                        @foreach ($activeLeases as $lease)
                            <p class="mb-1">
                                <a href="{{ route('portal.leases.show', $lease) }}">{{ $lease->unit->unit_number }} &middot; {{ $lease->unit->building->property->name }}</a>
                            </p>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h2 class="h6 mb-3">Recent Maintenance Requests</h2>
            @if ($recentMaintenanceRequests->isEmpty())
                <p class="text-secondary small mb-0">No maintenance requests yet.</p>
            @else
                <ul class="list-unstyled mb-0">
                    @foreach ($recentMaintenanceRequests as $request)
                        <li class="d-flex justify-content-between align-items-center py-1">
                            <span>{{ $request->title }}</span>
                            <span class="badge text-bg-{{ match($request->status) { 'completed' => 'success', 'cancelled' => 'secondary', 'in_progress' => 'primary', default => 'warning' } }} text-capitalize">{{ str_replace('_', ' ', $request->status) }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
@endsection
