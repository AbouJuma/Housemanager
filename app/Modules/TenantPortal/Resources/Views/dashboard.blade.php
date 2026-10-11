@extends('tenant-portal::layout')

@section('title', __('Dashboard'))

@section('portal-hero')
    <div class="page-hero-eyebrow">{{ company_now()->locale(app()->getLocale())->isoFormat('dddd, D MMMM YYYY') }}</div>
    <h1 class="h2 mb-2">{{ __('Welcome home, :name', ['name' => $tenant->name]) }}</h1>
    <p class="text-secondary mb-3" style="max-width: 36rem;">{{ __('Your lease, invoices and maintenance requests - all in one place.') }}</p>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('portal.invoices.index') }}" class="btn btn-primary"><i class="bi bi-receipt me-1"></i> {{ __('View invoices') }}</a>
        <a href="{{ route('portal.maintenance-requests.create') }}" class="btn btn-light"><i class="bi bi-tools me-1"></i> {{ __('Report an issue') }}</a>
    </div>
@endsection

@section('content')
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card kpi-card h-100">
                <div class="card-body d-flex gap-3">
                    <span class="icon-tile icon-tile-rose"><i class="bi bi-wallet2"></i></span>
                    <div>
                        <h2 class="h6 text-secondary mb-1">{{ __('Outstanding Balance') }}</h2>
                        <p class="display-6 fw-semibold mb-0">{{ number_format($outstandingBalance, 2) }}</p>
                        <p class="text-secondary small mb-0">{{ $outstandingInvoices->count() }} {{ trans_choice('unpaid invoice|unpaid invoices', $outstandingInvoices->count()) }}</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card kpi-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <span class="icon-tile icon-tile-brand"><i class="bi bi-house-heart"></i></span>
                        <h2 class="h6 text-secondary mb-0">{{ trans_choice('Active lease|Active leases', $activeLeases->count()) }}</h2>
                    </div>
                    @if ($activeLeases->isEmpty())
                        <p class="text-secondary small mb-0">{{ __('No active lease on file.') }}</p>
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

    <div class="card">
        <div class="card-body">
            <h2 class="h6 mb-3 d-flex align-items-center gap-2"><span class="icon-tile icon-tile-amber" style="width: 32px; height: 32px; font-size: 1rem;"><i class="bi bi-tools"></i></span> {{ __('Recent Maintenance Requests') }}</h2>
            @if ($recentMaintenanceRequests->isEmpty())
                <p class="text-secondary small mb-0">{{ __('No maintenance requests yet.') }}</p>
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
