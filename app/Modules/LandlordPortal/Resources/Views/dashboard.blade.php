@extends('landlord-portal::layout')

@section('title', __('Dashboard'))

@section('portal-hero')
    <div class="page-hero-eyebrow">{{ company_now()->locale(app()->getLocale())->isoFormat('dddd, D MMMM YYYY') }}</div>
    <h1 class="h2 mb-2">{{ __('Welcome back, :name', ['name' => $landlord->name]) }}</h1>
    <p class="text-secondary mb-3" style="max-width: 36rem;">{{ __('Occupancy, rent collected and expenses across your portfolio.') }}</p>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('landlord-portal.properties.index') }}" class="btn btn-primary"><i class="bi bi-buildings me-1"></i> {{ __('My properties') }}</a>
        <a href="{{ route('landlord-portal.expenses.index') }}" class="btn btn-light"><i class="bi bi-cash-stack me-1"></i> {{ __('Expenses') }}</a>
    </div>
@endsection

@section('content')

    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card kpi-card h-100">
                <div class="card-body">
                    <span class="icon-tile icon-tile-brand mb-3"><i class="bi bi-buildings"></i></span>
                    <h2 class="h6 text-secondary mb-1">{{ __('Properties') }}</h2>
                    <p class="display-6 fw-semibold mb-0">{{ $properties->count() }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card kpi-card h-100">
                <div class="card-body">
                    <span class="icon-tile icon-tile-violet mb-3"><i class="bi bi-door-open"></i></span>
                    <h2 class="h6 text-secondary mb-1">{{ __('Units') }}</h2>
                    <p class="display-6 fw-semibold mb-0">{{ $unitCounts['total'] }}</p>
                    <p class="text-secondary small mb-0">{{ $unitCounts['occupied'] }} occupied &middot; {{ $unitCounts['vacant'] }} vacant</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card kpi-card h-100">
                <div class="card-body">
                    <span class="icon-tile icon-tile-green mb-3"><i class="bi bi-cash-coin"></i></span>
                    <h2 class="h6 text-secondary mb-1">{{ __('Rent Collected') }}</h2>
                    <p class="display-6 fw-semibold mb-0">{{ number_format($totalCollected, 2) }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card kpi-card h-100">
                <div class="card-body">
                    <span class="icon-tile icon-tile-amber mb-3"><i class="bi bi-graph-up-arrow"></i></span>
                    <h2 class="h6 text-secondary mb-1">{{ __('Net') }}</h2>
                    <p class="display-6 fw-semibold mb-0">{{ number_format($netAmount, 2) }}</p>
                    <p class="text-secondary small mb-0">{{ number_format($totalExpenses, 2) }} in expenses</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h2 class="h6 mb-3 d-flex align-items-center gap-2"><span class="icon-tile icon-tile-amber" style="width: 32px; height: 32px; font-size: 1rem;"><i class="bi bi-tools"></i></span> {{ __('Recent Maintenance Requests') }}</h2>
            @if ($recentMaintenanceRequests->isEmpty())
                <p class="text-secondary small mb-0">{{ __('No maintenance requests yet.') }}</p>
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
