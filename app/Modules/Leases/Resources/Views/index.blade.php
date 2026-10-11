@extends('layouts.app')

@section('title', __('Leases'))

@section('page-heading')
    <h1 class="h4 mb-0">{{ __('Leases') }}</h1>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0 d-lg-none">{{ __('Leases') }}</h1>
            <p class="text-secondary mb-0">{{ $leases->total() }} {{ Str::plural('lease', $leases->total()) }}</p>
        </div>
        @can('create', \App\Modules\Leases\Models\Lease::class)
            <a href="{{ route('leases.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> {{ __('New Lease') }}
            </a>
        @endcan
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-auto">
                    <div class="input-group" style="max-width: 320px;">
                        <span class="input-group-text bg-transparent"><i class="bi bi-search"></i></span>
                        <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search by tenant or unit…">
                    </div>
                </div>
                <div class="col-auto">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">{{ __('All statuses') }}</option>
                        @foreach (['active', 'expired', 'terminated'] as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
            </form>

            @if ($leases->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-file-earmark-text display-4 text-secondary"></i>
                    <p class="mt-3 mb-1 fw-semibold">No leases yet</p>
                    <p class="text-secondary">Leases you create will show up here.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>{{ __('Tenant') }}</th>
                                <th>{{ __('Unit') }}</th>
                                <th>{{ __('Dates') }}</th>
                                <th>{{ __('Rent') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th class="text-end">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($leases as $lease)
                                <tr>
                                    <td class="fw-semibold">{{ $lease->tenant->name }}</td>
                                    <td>
                                        <div>{{ $lease->unit->unit_number }}</div>
                                        <div class="small text-secondary">{{ $lease->unit->building->property->name }}</div>
                                    </td>
                                    <td class="small">{{ $lease->start_date->format('M j, Y') }} &ndash; {{ $lease->end_date->format('M j, Y') }}</td>
                                    <td>{{ number_format($lease->rent_amount, 2) }}</td>
                                    <td>
                                        <span class="badge text-bg-{{ $lease->status === 'active' ? 'success' : ($lease->status === 'expired' ? 'secondary' : 'danger') }} text-capitalize">{{ $lease->status }}</span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('leases.show', $lease) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                                        @can('update', $lease)
                                            <a href="{{ route('leases.edit', $lease) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                                        @endcan
                                        @can('delete', $lease)
                                            <form action="{{ route('leases.destroy', $lease) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn btn-sm btn-outline-danger js-confirm-delete" data-name="this lease">
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
                    {{ $leases->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
