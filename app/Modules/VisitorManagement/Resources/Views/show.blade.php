@extends('layouts.app')

@section('title', $visitor->visitor_name)

@section('page-heading')
    <h1 class="h4 mb-0">{{ $visitor->visitor_name }}</h1>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0 d-lg-none">{{ $visitor->visitor_name }}</h1>
            <p class="text-secondary mb-0">
                @if ($visitor->isCheckedIn())
                    <span class="badge text-bg-success">{{ __('Checked In') }}</span>
                @else
                    <span class="badge text-bg-secondary">{{ __('Checked Out') }}</span>
                @endif
                <span class="text-capitalize">{{ $visitor->purpose }}</span>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('visitors.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> All Visitors
            </a>
            @can('update', $visitor)
                @if ($visitor->isCheckedIn())
                    <form action="{{ route('visitors.check-out', $visitor) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-success btn-sm">
                            <i class="bi bi-box-arrow-right me-1"></i> Check Out
                        </button>
                    </form>
                @endif
                <a href="{{ route('visitors.edit', $visitor) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-pencil me-1"></i> {{ __('Edit') }}
                </a>
            @endcan
            @can('delete', $visitor)
                <form action="{{ route('visitors.destroy', $visitor) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="button" class="btn btn-outline-danger btn-sm js-confirm-delete" data-name="this visitor record">
                        <i class="bi bi-trash me-1"></i> {{ __('Delete') }}
                    </button>
                </form>
            @endcan
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h2 class="h6 mb-3">{{ __('Details') }}</h2>
            <div class="row g-3 small">
                <div class="col-md-4">
                    <div class="text-secondary">{{ __('Property') }}</div>
                    <div><a href="{{ route('properties.show', $visitor->property) }}">{{ $visitor->property->name }}</a></div>
                </div>
                <div class="col-md-4">
                    <div class="text-secondary">{{ __('Unit') }}</div>
                    <div>
                        @if ($visitor->unit)
                            <a href="{{ route('units.show', $visitor->unit) }}">{{ $visitor->unit->unit_number }}</a>
                        @else
                            —
                        @endif
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-secondary">{{ __('Visiting') }}</div>
                    <div>
                        @if ($visitor->tenant)
                            <a href="{{ route('tenants.show', $visitor->tenant) }}">{{ $visitor->tenant->name }}</a>
                        @else
                            {{ $visitor->host_name ?? '—' }}
                        @endif
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-secondary">{{ __('Represents Vendor') }}</div>
                    <div>
                        @if ($visitor->vendor)
                            <a href="{{ route('vendors.show', $visitor->vendor) }}">{{ $visitor->vendor->name }}</a>
                        @else
                            —
                        @endif
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-secondary">{{ __('Phone') }}</div>
                    <div>{{ $visitor->visitor_phone ?? '—' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-secondary">{{ __('Vehicle Plate') }}</div>
                    <div>{{ $visitor->vehicle_plate ?? '—' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-secondary">{{ __('Badge Number') }}</div>
                    <div>{{ $visitor->badge_number ?? '—' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-secondary">Check-In Time</div>
                    <div>{{ $visitor->check_in_at->format('M j, Y g:i A') }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-secondary">Check-Out Time</div>
                    <div>{{ $visitor->check_out_at?->format('M j, Y g:i A') ?? '—' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-secondary">Checked In By</div>
                    <div>{{ $visitor->checkedInBy?->name ?? '—' }}</div>
                </div>
                @if ($visitor->checkedOutBy)
                    <div class="col-md-4">
                        <div class="text-secondary">Checked Out By</div>
                        <div>{{ $visitor->checkedOutBy->name }}</div>
                    </div>
                @endif
            </div>
            @if ($visitor->notes)
                <hr>
                <div class="text-secondary small mb-1">{{ __('Notes') }}</div>
                <p class="mb-0">{{ $visitor->notes }}</p>
            @endif
        </div>
    </div>
@endsection
