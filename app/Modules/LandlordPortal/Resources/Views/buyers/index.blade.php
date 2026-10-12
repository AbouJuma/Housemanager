@extends('landlord-portal::layout')

@section('title', __('My Buyers'))

@section('content')
    <h1 class="h4 mb-4">{{ __('My Buyers') }}</h1>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="GET" class="mb-3">
                <div class="input-group" style="max-width: 360px;">
                    <span class="input-group-text bg-transparent"><i class="bi bi-search"></i></span>
                    <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="{{ __('Search by name or phone') }}">
                </div>
            </form>

            @if ($buyers->isEmpty())
                <p class="text-secondary small mb-0">{{ __('No buyers yet.') }}</p>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Phone') }}</th>
                                <th>{{ __('Bought') }}</th>
                                <th>{{ __('Paid') }}</th>
                                @if ($canUpdate)<th></th>@endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($buyers as $buyer)
                                <tr>
                                    <td class="fw-semibold">{{ $buyer->name }}<div class="small text-secondary">&#64;{{ $buyer->username }}</div></td>
                                    <td>{{ $buyer->phone ?? '—' }}</td>
                                    <td class="small">
                                        @foreach ($buyer->sales as $sale)
                                            <div>{{ $sale->unit?->building?->property?->name }} · {{ __('Unit') }} {{ $sale->unit?->unit_number }}</div>
                                        @endforeach
                                    </td>
                                    <td class="small">
                                        @foreach ($buyer->sales as $sale)
                                            <div>{{ number_format($sale->total_paid, 2) }} / {{ number_format((float) $sale->total_price, 2) }} <span class="text-secondary">({{ $sale->completion_percentage }}%)</span></div>
                                        @endforeach
                                    </td>
                                    @if ($canUpdate)
                                        <td class="text-end"><a href="{{ route('landlord-portal.buyers.edit', $buyer) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('Edit') }}"><i class="bi bi-pencil"></i></a></td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">{{ $buyers->links() }}</div>
            @endif
        </div>
    </div>
@endsection
