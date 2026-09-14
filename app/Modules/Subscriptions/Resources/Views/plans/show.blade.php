@extends('layouts.app')

@section('title', $plan->name)

@section('page-heading')
    <h1 class="h4 mb-0">{{ $plan->name }}</h1>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0 d-lg-none">{{ $plan->name }}</h1>
            <span class="badge text-bg-{{ $plan->is_active ? 'success' : 'secondary' }}">{{ $plan->is_active ? 'Active' : 'Inactive' }}</span>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('subscription-plans.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> All Plans
            </a>
            @can('update', $plan)
                <a href="{{ route('subscription-plans.edit', $plan) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-pencil me-1"></i> Edit
                </a>
            @endcan
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4 text-secondary fw-normal">Slug</dt>
                        <dd class="col-sm-8">{{ $plan->slug }}</dd>
                        <dt class="col-sm-4 text-secondary fw-normal">Price</dt>
                        <dd class="col-sm-8">{{ number_format($plan->price, 2) }} / {{ $plan->billing_cycle }}</dd>
                        <dt class="col-sm-4 text-secondary fw-normal">Max Properties</dt>
                        <dd class="col-sm-8">{{ $plan->limitLabel($plan->max_properties) }}</dd>
                        <dt class="col-sm-4 text-secondary fw-normal">Max Units</dt>
                        <dd class="col-sm-8">{{ $plan->limitLabel($plan->max_units) }}</dd>
                        <dt class="col-sm-4 text-secondary fw-normal">Max Users</dt>
                        <dd class="col-sm-8">{{ $plan->limitLabel($plan->max_users) }}</dd>
                        <dt class="col-sm-4 text-secondary fw-normal">Sort Order</dt>
                        <dd class="col-sm-8">{{ $plan->sort_order }}</dd>
                        @if ($plan->description)
                            <dt class="col-sm-4 text-secondary fw-normal">Description</dt>
                            <dd class="col-sm-8">{{ $plan->description }}</dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="display-6">{{ $subscriberCount }}</div>
                    <p class="text-secondary mb-0">{{ Str::plural('subscriber', $subscriberCount) }}</p>
                </div>
            </div>
        </div>
    </div>
@endsection
