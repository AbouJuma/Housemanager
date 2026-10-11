@extends('layouts.app')

@section('title', $expense->description)

@section('page-heading')
    <h1 class="h4 mb-0">{{ $expense->description }}</h1>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0 d-lg-none">{{ $expense->description }}</h1>
            <p class="text-secondary mb-0">
                @if ($expense->isVoided())
                    <span class="badge text-bg-secondary">{{ __('Voided') }}</span>
                @else
                    <span class="badge text-bg-{{ $expense->isPaid() ? 'success' : 'warning' }} text-capitalize">{{ $expense->payment_status }}</span>
                @endif
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('expenses.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> {{ __('All Expenses') }}
            </a>
            @can('update', $expense)
                @if (! $expense->isVoided())
                    <a href="{{ route('expenses.edit', $expense) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-pencil me-1"></i> {{ __('Edit') }}
                    </a>
                    <form action="{{ route('expenses.void', $expense) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="button" class="btn btn-outline-danger btn-sm js-confirm-delete" data-name="this expense" data-text="This reverses its ledger entries and cannot be undone.">
                            <i class="bi bi-x-lg me-1"></i> {{ __('Void') }}
                        </button>
                    </form>
                @endif
            @endcan
        </div>
    </div>

    @if ($expense->isVoided())
        <div class="alert alert-secondary">
            <i class="bi bi-x-circle me-1"></i> This expense has been voided. Its journal entries were reversed.
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h6 mb-3">{{ __('Details') }}</h2>
                    <div class="row g-3 small">
                        <div class="col-md-4">
                            <div class="text-secondary">{{ __('Category') }}</div>
                            <div>{{ $expense->account->code }} &middot; {{ $expense->account->name }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-secondary">{{ __('Property') }}</div>
                            <div>{{ $expense->property->name ?? '—' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-secondary">{{ __('Vendor') }}</div>
                            <div>
                                @if ($expense->vendor)
                                    <a href="{{ route('vendors.show', $expense->vendor) }}">{{ $expense->vendor->name }}</a>
                                @else
                                    {{ $expense->vendor_name ?? '—' }}
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-secondary">{{ __('Amount') }}</div>
                            <div>{{ number_format($expense->amount, 2) }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-secondary">{{ __('Expense Date') }}</div>
                            <div>{{ $expense->expense_date->toFormattedDateString() }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-secondary">{{ __('Reference') }}</div>
                            <div>{{ $expense->reference ?? '—' }}</div>
                        </div>
                        @if ($expense->isPaid())
                            <div class="col-md-4">
                                <div class="text-secondary">{{ __('Paid On') }}</div>
                                <div>{{ $expense->paid_on?->toFormattedDateString() }}</div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-secondary">{{ __('Payment Method') }}</div>
                                <div class="text-capitalize">{{ str_replace('_', ' ', $expense->payment_method) }}</div>
                            </div>
                        @endif
                        <div class="col-md-4">
                            <div class="text-secondary">{{ __('Recorded By') }}</div>
                            <div>{{ $expense->recordedBy?->name ?? 'System' }}</div>
                        </div>
                    </div>
                    @if ($expense->notes)
                        <hr>
                        <div class="text-secondary small mb-1">{{ __('Notes') }}</div>
                        <p class="mb-0">{{ $expense->notes }}</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            @can('update', $expense)
                @if (! $expense->isPaid() && ! $expense->isVoided())
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h2 class="h6 mb-3">{{ __('Mark as Paid') }}</h2>
                            <form method="POST" action="{{ route('expenses.mark-paid', $expense) }}">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label">{{ __('Payment Method') }}</label>
                                    <select name="payment_method" class="form-select @error('payment_method') is-invalid @enderror">
                                        @foreach (['cash', 'bank_transfer', 'cheque', 'mobile_money', 'other'] as $method)
                                            <option value="{{ $method }}" @selected(old('payment_method') === $method)>{{ ucfirst(str_replace('_', ' ', $method)) }}</option>
                                        @endforeach
                                    </select>
                                    @error('payment_method') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">{{ __('Paid On') }}</label>
                                    <input type="date" name="paid_on" value="{{ old('paid_on', now()->toDateString()) }}" class="form-control @error('paid_on') is-invalid @enderror">
                                    @error('paid_on') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm">{{ __('Mark as Paid') }}</button>
                            </form>
                        </div>
                    </div>
                @endif
            @endcan
        </div>
    </div>
@endsection
