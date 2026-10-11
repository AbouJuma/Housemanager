@extends('layouts.app')

@section('title', __('Reports'))

@section('page-heading')
    <h1 class="h4 mb-0">{{ __('Reports') }}</h1>
@endsection

@section('content')
    <div class="row g-4">
        <div class="col-md-6 col-lg-3">
            <a href="{{ route('reports.rent-roll') }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <i class="bi bi-house-door fs-3 text-primary"></i>
                        <h2 class="h6 mt-3 mb-1">{{ __('Rent Roll') }}</h2>
                        <p class="text-secondary small mb-0">Every unit, its tenant, lease dates, and rent amount.</p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-6 col-lg-3">
            <a href="{{ route('reports.occupancy') }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <i class="bi bi-pie-chart fs-3 text-primary"></i>
                        <h2 class="h6 mt-3 mb-1">{{ __('Occupancy') }}</h2>
                        <p class="text-secondary small mb-0">Occupied vs. vacant units, per property.</p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-6 col-lg-3">
            <a href="{{ route('reports.income-statement') }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <i class="bi bi-graph-up-arrow fs-3 text-primary"></i>
                        <h2 class="h6 mt-3 mb-1">{{ __('Income Statement') }}</h2>
                        <p class="text-secondary small mb-0">Income and expenses for a date range, from the ledger.</p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-6 col-lg-3">
            <a href="{{ route('reports.ar-aging') }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <i class="bi bi-hourglass-split fs-3 text-primary"></i>
                        <h2 class="h6 mt-3 mb-1">{{ __('AR Aging') }}</h2>
                        <p class="text-secondary small mb-0">Outstanding invoices, grouped by how overdue they are.</p>
                    </div>
                </div>
            </a>
        </div>
        @if (auth()->user()->isSaasOwner())
            <div class="col-md-6 col-lg-3">
                <a href="{{ route('reports.subscription-status') }}" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <i class="bi bi-card-checklist fs-3 text-primary"></i>
                            <h2 class="h6 mt-3 mb-1">{{ __('Subscription Status') }}</h2>
                            <p class="text-secondary small mb-0">Current company subscriptions, current periods, and unpaid invoice counts.</p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-6 col-lg-3">
                <a href="{{ route('reports.billing-status') }}" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <i class="bi bi-receipt-cutoff fs-3 text-primary"></i>
                            <h2 class="h6 mt-3 mb-1">{{ __('Billing Status') }}</h2>
                            <p class="text-secondary small mb-0">Latest subscription invoices across companies, with overdue and paid status.</p>
                        </div>
                    </div>
                </a>
            </div>
        @endif
    </div>
@endsection
