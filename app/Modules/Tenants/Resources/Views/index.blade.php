@extends('layouts.app')

@section('title', __('Tenants'))

@section('page-heading')
    <h1 class="h4 mb-0">{{ __('Tenants') }}</h1>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0 d-lg-none">{{ __('Tenants') }}</h1>
            <p class="text-secondary mb-0">{{ $tenants->total() }} {{ Str::plural('tenant', $tenants->total()) }}</p>
        </div>
        @can('create', \App\Modules\Tenants\Models\Tenant::class)
            <a href="{{ route('tenants.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> {{ __('New Tenant') }}
            </a>
        @endcan
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-auto">
                    <div class="input-group" style="max-width: 320px;">
                        <span class="input-group-text bg-transparent"><i class="bi bi-search"></i></span>
                        <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search tenants…">
                    </div>
                </div>
                <div class="col-auto">
                    <select name="type" class="form-select" onchange="this.form.submit()">
                        <option value="">{{ __('All types') }}</option>
                        @foreach (['individual', 'company'] as $type)
                            <option value="{{ $type }}" @selected(request('type') === $type)>{{ ucfirst($type) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">{{ __('All statuses') }}</option>
                        @foreach (['active', 'inactive', 'blacklisted'] as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
            </form>

            @if ($tenants->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-people display-4 text-secondary"></i>
                    <p class="mt-3 mb-1 fw-semibold">No tenants yet</p>
                    <p class="text-secondary">Tenants you add will show up here.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>{{ __('Tenant') }}</th>
                                <th>{{ __('Type') }}</th>
                                <th>{{ __('Contact') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th class="text-end">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tenants as $tenant)
                                <tr>
                                    <td class="fw-semibold">{{ $tenant->name }}</td>
                                    <td class="text-capitalize">{{ $tenant->type }}</td>
                                    <td>
                                        <div class="small">{{ $tenant->email ?? '—' }}</div>
                                        <div class="small text-secondary">{{ $tenant->phone ?? '' }}</div>
                                    </td>
                                    <td>
                                        <span class="badge text-bg-{{ match ($tenant->status) {
                                            'active' => 'success',
                                            'blacklisted' => 'danger',
                                            default => 'secondary',
                                        } }} text-capitalize">{{ $tenant->status }}</span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('tenants.show', $tenant) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                                        @can('update', $tenant)
                                            <a href="{{ route('tenants.edit', $tenant) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                                        @endcan
                                        @can('delete', $tenant)
                                            <form action="{{ route('tenants.destroy', $tenant) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn btn-sm btn-outline-danger js-confirm-delete" data-name="{{ $tenant->name }}">
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
                    {{ $tenants->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
