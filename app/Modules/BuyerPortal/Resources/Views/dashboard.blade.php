@extends('buyer-portal::layout')

@section('title', __('Dashboard'))

@section('content')
    @php
        $money = fn ($value) => number_format((float) $value, 2);
        $progress = $totalPrice > 0 ? min(100, round($totalPaid / $totalPrice * 100)) : 0;
    @endphp

    <h1 class="h4 mb-4">{{ __('Hello, :name', ['name' => auth('buyer')->user()->name]) }}</h1>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100"><div class="card-body">
                <div class="text-secondary small">{{ __('Paid so far') }}</div>
                <div class="h4 mb-2 text-success">{{ $money($totalPaid) }}</div>
                <div class="progress" style="height: 6px;" role="progressbar" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar bg-success" style="width: {{ $progress }}%"></div>
                </div>
                <div class="small text-secondary mt-1">{{ __(':percent% of :total', ['percent' => $progress, 'total' => $money($totalPrice)]) }}</div>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100"><div class="card-body">
                <div class="text-secondary small">{{ __('Outstanding') }}</div>
                <div class="h4 mb-1 {{ $outstandingBalance > 0 ? 'text-danger' : '' }}">{{ $money($outstandingBalance) }}</div>
                <div class="small text-secondary">{{ __('Unpaid invoices: :count', ['count' => $outstandingCount]) }}</div>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100"><div class="card-body">
                <div class="text-secondary small">{{ __('Next instalment') }}</div>
                @if ($nextInstallment)
                    <div class="h4 mb-1">{{ $money($nextInstallment->amount - $nextInstallment->paid_amount) }}</div>
                    <div class="small text-secondary">{{ __('Due :date', ['date' => $nextInstallment->due_date->format('d M Y')]) }}</div>
                @else
                    <div class="h5 mb-1 text-success">{{ __('Nothing due') }}</div>
                @endif
            </div></div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h6 mb-0">{{ __('My Purchases') }}</h2>
                <a href="{{ route('buyer-portal.invoices.index') }}" class="small">{{ __('All invoices') }} <i class="bi bi-arrow-right"></i></a>
            </div>
            @forelse ($sales as $sale)
                @include('buyer-portal::purchases._card', ['sale' => $sale])
            @empty
                <p class="text-secondary small mb-0">{{ __('No purchases on file yet.') }}</p>
            @endforelse
        </div>
    </div>
@endsection
