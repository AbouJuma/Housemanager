@extends('landlord-portal::layout')

@section('title', 'Dashboard')

@section('content')
    <h1 class="h4 mb-4">Welcome, {{ $landlord->name }}</h1>

    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 text-secondary mb-2">Properties</h2>
                    <p class="display-6 mb-0">{{ $properties->count() }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 text-secondary mb-2">Units</h2>
                    <p class="display-6 mb-0">{{ $unitCounts['total'] }}</p>
                    <p class="text-secondary small mb-0">{{ $unitCounts['occupied'] }} occupied &middot; {{ $unitCounts['vacant'] }} vacant</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 text-secondary mb-2">Rent Collected</h2>
                    <p class="display-6 mb-0">{{ number_format($totalCollected, 2) }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 text-secondary mb-2">Net</h2>
                    <p class="display-6 mb-0">{{ number_format($netAmount, 2) }}</p>
                    <p class="text-secondary small mb-0">{{ number_format($totalExpenses, 2) }} in expenses</p>
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
                            <span>{{ $request->title }} <span class="text-secondary small">&middot; {{ $request->property->name }}</span></span>
                            <span class="badge text-bg-{{ match($request->status) { 'completed' => 'success', 'cancelled' => 'secondary', 'in_progress' => 'primary', default => 'warning' } }} text-capitalize">{{ str_replace('_', ' ', $request->status) }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
@endsection
