@extends('layouts.app')

@section('title', __('Sales'))

@section('page-heading')
    <h1 class="h4 mb-0">{{ __('Sales') }}</h1>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0 d-lg-none">{{ __('Sales') }}</h1>
        </div>
        <div class="d-flex gap-2">
            @can('create', \App\Modules\Sales\Models\Sale::class)
                <a href="{{ route('sales.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> {{ __('New Sale') }}
                </a>
            @endcan
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="GET" class="row g-3 mb-3">
                <div class="col-md-4">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search by unit or buyer...">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">{{ __('All Statuses') }}</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>{{ __('Pending') }}</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>{{ __('Completed') }}</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>{{ __('Cancelled') }}</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="property_id" class="form-select">
                        <option value="">{{ __('All Properties') }}</option>
                        @foreach ($properties as $property)
                            <option value="{{ $property->id }}" {{ request('property_id') == $property->id ? 'selected' : '' }}>{{ $property->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-secondary w-100">{{ __('Filter') }}</button>
                </div>
            </form>

            @if ($sales->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-currency-dollar display-4 text-secondary"></i>
                    <p class="mt-3 mb-1 fw-semibold">No sales recorded yet</p>
                    <p class="text-secondary">Start by recording your first property sale.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('Unit') }}</th>
                                <th>{{ __('Buyer') }}</th>
                                <th>{{ __('Sale Date') }}</th>
                                <th>{{ __('Total Price') }}</th>
                                <th>{{ __('Paid') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th class="text-end">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sales as $index => $sale)
                                <tr>
                                    <td>{{ ($sales->currentPage() - 1) * $sales->perPage() + $index + 1 }}</td>
                                    <td class="fw-semibold">
                                        {{ $sale->unit->unit_number }}
                                        <small class="text-secondary d-block">{{ $sale->unit->building->name }}</small>
                                    </td>
                                    <td>
                                        @if ($sale->new_buyer_id)
                                            {{ $sale->buyer->name ?? '—' }}
                                        @else
                                            <span class="text-secondary">—</span>
                                        @endif
                                    </td>
                                    <td>{{ $sale->sale_date->format('M j, Y') }}</td>
                                    <td>{{ number_format($sale->total_price, 2) }}</td>
                                    <td>{{ number_format($sale->total_paid, 2) }}</td>
                                    <td>
                                        <span class="badge text-bg-{{ $sale->status === 'pending' ? 'secondary' : ($sale->status === 'active' ? 'primary' : ($sale->status === 'completed' ? 'success' : ($sale->status === 'cancelled' ? 'danger' : 'secondary'))) }}">{{ ucfirst($sale->status) }}</span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('sales.show', $sale) }}" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @can('delete', $sale)
                                            <form action="{{ route('sales.destroy', $sale) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn btn-sm btn-outline-danger js-confirm-delete" data-name="Sale #{{ $sale->id }}">
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
                    {{ $sales->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
