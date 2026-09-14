@extends('layouts.app')

@section('title', 'Dashboard')

@section('page-heading')
    <h1 class="h4 mb-0">Dashboard</h1>
@endsection

@section('content')
    <h1 class="h4 mb-3 d-lg-none">Dashboard</h1>

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
                        <h2 class="h6 mb-3">Companies by Status</h2>
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
                        <x-slot:footer>{{ $occupiedUnits }} of {{ $totalUnits }} occupied</x-slot:footer>
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

        @if (! empty($usersByRole))
            <div class="row g-3 mb-4">
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h2 class="h6 mb-3">Users by Role</h2>
                            <canvas id="rolesChart" height="220"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h2 class="h6 mb-3">Recent Activity</h2>

            @if ($recentActivity->isEmpty())
                <p class="text-secondary small mb-0">No activity yet.</p>
            @else
                <ul class="list-unstyled mb-0">
                    @foreach ($recentActivity as $entry)
                        <li class="d-flex justify-content-between align-items-start py-2 {{ ! $loop->last ? 'border-bottom' : '' }}">
                            <div>
                                <span class="badge text-bg-secondary me-1">{{ $entry->log_name }}</span>
                                <span class="text-capitalize">{{ $entry->event }}</span>
                                {{ class_basename($entry->subject_type) }}
                                @if ($isSaasOwner && $entry->company)
                                    <span class="text-secondary">&middot; {{ $entry->company->name }}</span>
                                @endif
                                <div class="small text-secondary">by {{ $entry->causer?->name ?? 'System' }}</div>
                            </div>
                            <span class="text-secondary small text-nowrap">{{ $entry->created_at->diffForHumans() }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script>
    @if ($isSaasOwner)
        new Chart(document.getElementById('signupsChart'), {
            type: 'line',
            data: {
                labels: @json(array_map(fn ($date) => \Illuminate\Support\Carbon::parse($date)->format('M j'), array_keys($signupsByDay))),
                datasets: [{
                    label: 'New Companies',
                    data: @json(array_values($signupsByDay)),
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
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
                    backgroundColor: ['#198754', '#0dcaf0', '#ffc107', '#6c757d'],
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
                    backgroundColor: ['#0d6efd', '#6c757d', '#198754', '#ffc107', '#dc3545'],
                }],
            },
            options: { plugins: { legend: { position: 'bottom' } } },
        });
    @endif
</script>
@endpush
