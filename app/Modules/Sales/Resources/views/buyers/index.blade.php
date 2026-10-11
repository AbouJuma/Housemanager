@extends('layouts.app')

@section('title', __('Buyers'))

@section('page-heading')
    <h1 class="h4 mb-0">{{ __('Buyers') }}</h1>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0 d-lg-none">{{ __('Buyers') }}</h1>
        </div>
        <div class="d-flex gap-2">
            @can('create', \App\Modules\Sales\Models\Buyer::class)
                <a href="{{ route('buyers.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> {{ __('New Buyer') }}
                </a>
            @endcan
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="GET" class="row g-3 mb-3">
                <div class="col-md-4">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search by name, email, or phone...">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">{{ __('All Statuses') }}</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>{{ __('Inactive') }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-secondary w-100">{{ __('Filter') }}</button>
                </div>
            </form>

            @if ($buyers->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-person display-4 text-secondary"></i>
                    <p class="mt-3 mb-1 fw-semibold">No buyers found</p>
                    <p class="text-secondary">Start by adding your first buyer.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Email') }}</th>
                                <th>{{ __('Phone') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th class="text-end">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($buyers as $index => $buyer)
                                <tr>
                                    <td>{{ ($buyers->currentPage() - 1) * $buyers->perPage() + $index + 1 }}</td>
                                    <td class="fw-semibold">{{ $buyer->name }}</td>
                                    <td>{{ $buyer->email ?? '—' }}</td>
                                    <td>{{ $buyer->phone ?? '—' }}</td>
                                    <td>
                                        <span class="badge text-bg-{{ $buyer->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($buyer->status) }}</span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('buyers.show', $buyer) }}" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @can('delete', $buyer)
                                            <form action="{{ route('buyers.destroy', $buyer) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn btn-sm btn-outline-danger js-confirm-delete" data-name="{{ $buyer->name }}">
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
                    {{ $buyers->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
