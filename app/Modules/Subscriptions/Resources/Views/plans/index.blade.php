@extends('layouts.app')

@section('title', __('Subscription Plans'))

@section('page-heading')
    <h1 class="h4 mb-0">{{ __('Subscription Plans') }}</h1>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0 d-lg-none">{{ __('Subscription Plans') }}</h1>
            <p class="text-secondary mb-0">{{ $plans->total() }} {{ Str::plural('plan', $plans->total()) }}</p>
        </div>
        @can('create', \App\Modules\Subscriptions\Models\SubscriptionPlan::class)
            <a href="{{ route('subscription-plans.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> New Plan
            </a>
        @endcan
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <x-kpi-card label="Active Subscriptions" :value="$overview['activeSubscriptionsCount']" icon="bi-check-circle" />
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-kpi-card label="On Trial" :value="$overview['trialingSubscriptionsCount']" icon="bi-hourglass-split" />
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-kpi-card label="MRR" :value="number_format($overview['mrr'], 2)" icon="bi-graph-up" />
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-kpi-card label="Unpaid Invoices" :value="$overview['unpaidInvoicesCount']" icon="bi-receipt">
                <x-slot:footer>{{ number_format($overview['unpaidInvoicesTotal'], 2) }} total{{ $overview['overdueInvoicesCount'] > 0 ? ', ' . $overview['overdueInvoicesCount'] . ' overdue' : '' }}</x-slot:footer>
            </x-kpi-card>
        </div>
    </div>

    @if ($overview['expiringSoonCount'] > 0)
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h2 class="h6 mb-3">{{ __('Expiring Soon') }} <span class="text-secondary fw-normal">(next 14 days)</span></h2>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('Company') }}</th>
                                <th>{{ __('Plan') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>Ends</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($overview['expiringSoon'] as $subscription)
                                <tr>
                                    <td>
                                        <a href="{{ route('companies.show', $subscription->company) }}">{{ $subscription->company->name }}</a>
                                    </td>
                                    <td>{{ $subscription->plan->name }}</td>
                                    <td>
                                        <span class="badge text-bg-{{ $subscription->status === 'active' ? 'success' : 'info' }}">{{ ucfirst($subscription->status) }}</span>
                                    </td>
                                    @php
                                        $daysRemaining = (int) floor($subscription->current_period_end->diffInDays(now(), false));
                                    @endphp
                                    <td class="small">{{ $subscription->current_period_end->format('M j, Y') }} ({{ $daysRemaining }}d)</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($overview['expiringSoonCount'] > $overview['expiringSoon']->count())
                    <p class="text-secondary small mb-0 mt-2">Showing {{ $overview['expiringSoon']->count() }} of {{ $overview['expiringSoonCount'] }}.</p>
                @endif
            </div>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="GET" class="mb-3">
                <div class="input-group" style="max-width: 320px;">
                    <span class="input-group-text bg-transparent"><i class="bi bi-search"></i></span>
                    <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search plans…">
                </div>
            </form>

            @if ($plans->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-card-checklist display-4 text-secondary"></i>
                    <p class="mt-3 mb-1 fw-semibold">No subscription plans yet</p>
                    <p class="text-secondary">Create a plan for companies to subscribe to.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>{{ __('Plan') }}</th>
                                <th>{{ __('Price') }}</th>
                                <th>Limits</th>
                                <th>Subscribers</th>
                                <th>{{ __('Status') }}</th>
                                <th class="text-end">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($plans as $plan)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $plan->name }}</div>
                                        <div class="text-secondary small">{{ $plan->slug }}</div>
                                    </td>
                                    <td>{{ number_format($plan->price, 2) }} / {{ $plan->billing_cycle }}</td>
                                    <td class="small text-secondary">
                                        {{ $plan->limitLabel($plan->max_properties) }} properties &middot;
                                        {{ $plan->limitLabel($plan->max_units) }} units &middot;
                                        {{ $plan->limitLabel($plan->max_users) }} users
                                    </td>
                                    <td>{{ $overview['subscribersByPlan'][$plan->id] ?? 0 }}</td>
                                    <td>
                                        <span class="badge text-bg-{{ $plan->is_active ? 'success' : 'secondary' }}">{{ $plan->is_active ? 'Active' : 'Inactive' }}</span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('subscription-plans.show', $plan) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                                        @can('update', $plan)
                                            <a href="{{ route('subscription-plans.edit', $plan) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                                        @endcan
                                        @can('delete', $plan)
                                            <form action="{{ route('subscription-plans.destroy', $plan) }}" method="POST" class="d-inline js-delete-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn btn-sm btn-outline-danger js-confirm-delete" data-name="{{ $plan->name }}" data-text="This can be restored by support, but is hidden from the app immediately.">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $plans->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
