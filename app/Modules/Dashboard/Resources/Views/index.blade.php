@extends('layouts.app')

@section('title', __('Dashboard'))

@section('hero-class', 'page-hero-lg')

@section('page-heading')
    @php
        $hour = company_now()->hour;
    @endphp
    <div class="page-hero-eyebrow">{{ company_now()->locale(app()->getLocale())->isoFormat('dddd, D MMMM YYYY') }}</div>
    <h1 class="h2 mb-2">{{ __($hour < 12 ? 'Good morning, :name' : ($hour < 17 ? 'Good afternoon, :name' : 'Good evening, :name'), ['name' => \Illuminate\Support\Str::before(auth()->user()->name, ' ')]) }}</h1>
    <p class="text-secondary mb-0" style="max-width: 38rem;">
        @if ($isSaasOwner)
            {{ __("Here's how the platform is doing today.") }}
        @else
            {{ __("Here's what's happening across your properties today.") }}
        @endif
    </p>
@endsection

@section('content')

    @if ($isSaasOwner)
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <x-kpi-card label="Total Companies" :value="$totalCompanies" icon="bi-building" />
            </div>
            <div class="col-sm-6 col-xl-3">
                <x-kpi-card label="Active Companies" :value="$activeCompanies" icon="bi-check-circle" />
            </div>
            <div class="col-sm-6 col-xl-3">
                <x-kpi-card label="On Trial" :value="$trialCompanies" icon="bi-hourglass-split" />
            </div>
            <div class="col-sm-6 col-xl-3">
                <x-kpi-card label="Total Users" :value="$totalUsers" icon="bi-people" />
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <x-kpi-card label="Active Subscriptions" :value="$activeSubscriptionsCount" icon="bi-check-circle" />
            </div>
            <div class="col-sm-6 col-xl-3">
                <x-kpi-card label="MRR" :value="number_format($mrr, 2)" icon="bi-graph-up" />
            </div>
            <div class="col-sm-6 col-xl-3">
                <x-kpi-card label="Expiring Soon" :value="$expiringSoonCount" icon="bi-hourglass-split">
                    <x-slot:footer>next 14 days</x-slot:footer>
                </x-kpi-card>
            </div>
            <div class="col-sm-6 col-xl-3">
                <x-kpi-card label="Unpaid Invoices" :value="$unpaidInvoicesCount" icon="bi-receipt">
                    <x-slot:footer>{{ number_format($unpaidInvoicesTotal, 2) }} total</x-slot:footer>
                </x-kpi-card>
            </div>
            <div class="col-sm-6 col-xl-3">
                <x-kpi-card
                    label="Platform SMS Balance"
                    :value="$smsBalance['success'] ? number_format($smsBalance['balance'], 2) : '—'"
                    icon="bi-chat-dots"
                >
                    <x-slot:footer>
                        @if ($smsBalance['success'])
                            <span class="text-secondary">Beem credits &middot; updates every minute</span>
                        @else
                            <span class="text-danger">{{ $smsBalance['message'] }}</span>
                        @endif
                    </x-slot:footer>
                </x-kpi-card>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="h6 mb-3">New Companies (Last 14 Days)</h2>
                        <canvas id="signupsChart" height="90"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="h6 mb-3">{{ __('Companies by Status') }}</h2>
                        <canvas id="statusChart" height="220"></canvas>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <x-kpi-card label="Total Users" :value="$totalUsers" icon="bi-people" />
            </div>
            <div class="col-sm-6 col-xl-3">
                <x-kpi-card label="Properties" :value="$totalProperties" icon="bi-buildings" />
            </div>
            <div class="col-sm-6 col-xl-3">
                <x-kpi-card label="Units" :value="$totalUnits" icon="bi-door-closed" />
            </div>
            @if ($occupancyRate !== null)
                <div class="col-sm-6 col-xl-3">
                    <x-kpi-card label="Occupancy Rate" :value="$occupancyRate . '%'" icon="bi-graph-up">
                        <x-slot:footer>{{ __(':occupied of :total occupied', ['occupied' => $occupiedUnits, 'total' => $totalUnits]) }}</x-slot:footer>
                    </x-kpi-card>
                </div>
            @endif
            @if ($company->isOnTrial())
                @php
                    $trialDaysRemaining = (int) floor($company->trial_ends_at->diffInDays(now(), false));
                    $trialLabel = $trialDaysRemaining <= 0 ? 'Expired' : $trialDaysRemaining . ' days';
                @endphp
                <div class="col-sm-6 col-xl-3">
                    <x-kpi-card label="Trial Ends" :value="$trialLabel" icon="bi-hourglass-split" />
                </div>
            @endif
        </div>

    @endif

    @php
        // Users by Role (staff dashboards) shares a row with Recent Activity.
        $showRolesChart = ! $isSaasOwner && ! empty($usersByRole);
    @endphp
    <div class="row g-3">
        @if ($showRolesChart)
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h2 class="h6 mb-3">{{ __('Users by Role') }}</h2>
                        <canvas id="rolesChart" height="220"></canvas>
                    </div>
                </div>
            </div>
        @endif
    <div class="{{ $showRolesChart ? 'col-lg-8' : 'col-12' }}">
    <div class="card h-100">
        <div class="card-body d-flex flex-column">
            <h2 class="h6 mb-3">{{ __('Recent Activity') }}</h2>

            @if ($recentActivity->isEmpty())
                <p class="text-secondary small mb-0">{{ __('No activity yet.') }}</p>
            @else
                <ul class="list-unstyled mb-0 overflow-auto" style="max-height: 340px;">
                    @foreach ($recentActivity as $entry)
                        <li class="d-flex justify-content-between align-items-start py-2 {{ ! $loop->last ? 'border-bottom' : '' }}">
                            <div>
                                <span class="badge text-bg-secondary me-1">{{ $entry->log_name }}</span>
                                <span class="text-capitalize">{{ $entry->event }}</span>
                                {{ class_basename($entry->subject_type) }}
                                @if ($isSaasOwner && $entry->company)
                                    <span class="text-secondary">&middot; {{ $entry->company->name }}</span>
                                @endif
                                <div class="small text-secondary">{{ __('by :name', ['name' => $entry->causer?->name ?? __('System')]) }}</div>
                            </div>
                            <span class="text-secondary small text-nowrap">{{ $entry->created_at->diffForHumans() }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
    </div>
    </div>
@endsection

@push('scripts')
<script>
    // DOMContentLoaded: Chart is set on window by app.js, a deferred module
    // that runs after this inline script would otherwise execute.
    document.addEventListener('DOMContentLoaded', function () {
    // Brand color comes from the company's own theme tokens (see partials/head).
    const brandColor = getComputedStyle(document.documentElement).getPropertyValue('--brand').trim() || '#1F5A94';
    const brandRgb = getComputedStyle(document.documentElement).getPropertyValue('--brand-rgb').trim() || '31, 90, 148';
    const chartPalette = [brandColor, '#2DD4BF', '#6366F1', '#0EA5E9', '#F43F5E', '#10B981', '#94A3B8'];

    @if ($isSaasOwner)
        new Chart(document.getElementById('signupsChart'), {
            type: 'line',
            data: {
                labels: @json(array_map(fn ($date) => \Illuminate\Support\Carbon::parse($date)->format('M j'), array_keys($signupsByDay))),
                datasets: [{
                    label: 'New Companies',
                    data: @json(array_values($signupsByDay)),
                    borderColor: brandColor,
                    backgroundColor: `rgba(${brandRgb}, 0.12)`,
                    fill: true,
                    tension: 0.3,
                }],
            },
            options: {
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
            },
        });

        new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: {
                labels: @json(array_map('ucfirst', array_keys($companiesByStatus->all()))),
                datasets: [{
                    data: @json(array_values($companiesByStatus->all())),
                    backgroundColor: chartPalette,
                    borderWidth: 0,
                }],
            },
            options: { plugins: { legend: { position: 'bottom' } } },
        });
    @elseif (! empty($usersByRole))
        new Chart(document.getElementById('rolesChart'), {
            type: 'doughnut',
            data: {
                labels: @json(array_keys($usersByRole)),
                datasets: [{
                    data: @json(array_values($usersByRole)),
                    backgroundColor: chartPalette,
                    borderWidth: 0,
                }],
            },
            options: { plugins: { legend: { position: 'bottom' } } },
        });
    @endif
    });
</script>
@endpush
