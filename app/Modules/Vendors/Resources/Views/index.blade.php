@extends('layouts.app')

@section('title', 'Vendors')

@section('page-heading')
    <h1 class="h4 mb-0">Vendors</h1>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0 d-lg-none">Vendors</h1>
            <p class="text-secondary mb-0">{{ $vendors->total() }} {{ Str::plural('vendor', $vendors->total()) }}</p>
        </div>
        @can('create', \App\Modules\Vendors\Models\Vendor::class)
            <a href="{{ route('vendors.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> New Vendor
            </a>
        @endcan
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-auto">
                    <div class="input-group" style="max-width: 320px;">
                        <span class="input-group-text bg-transparent"><i class="bi bi-search"></i></span>
                        <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search vendors…">
                    </div>
                </div>
                <div class="col-auto">
                    <select name="category" class="form-select" onchange="this.form.submit()">
                        <option value="">All categories</option>
                        @foreach (['plumbing', 'electrical', 'hvac', 'cleaning', 'landscaping', 'pest_control', 'general_contractor', 'utility_provider', 'security', 'other'] as $category)
                            <option value="{{ $category }}" @selected(request('category') === $category)>{{ ucfirst(str_replace('_', ' ', $category)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">All statuses</option>
                        @foreach (['active', 'inactive'] as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
            </form>

            @if ($vendors->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-truck display-4 text-secondary"></i>
                    <p class="mt-3 mb-1 fw-semibold">No vendors yet</p>
                    <p class="text-secondary">Vendors you add will show up here.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Vendor</th>
                                <th>Category</th>
                                <th>Contact</th>
                                <th>Expenses</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($vendors as $vendor)
                                <tr>
                                    <td class="fw-semibold">{{ $vendor->name }}</td>
                                    <td class="small text-secondary text-capitalize">{{ str_replace('_', ' ', $vendor->category) }}</td>
                                    <td>
                                        <div class="small">{{ $vendor->email ?? '—' }}</div>
                                        <div class="small text-secondary">{{ $vendor->phone ?? '' }}</div>
                                    </td>
                                    <td>{{ $vendor->expenses_count }}</td>
                                    <td>
                                        <span class="badge text-bg-{{ $vendor->status === 'active' ? 'success' : 'secondary' }} text-capitalize">{{ $vendor->status }}</span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('vendors.show', $vendor) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                                        @can('update', $vendor)
                                            <a href="{{ route('vendors.edit', $vendor) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                                        @endcan
                                        @can('delete', $vendor)
                                            <form action="{{ route('vendors.destroy', $vendor) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn btn-sm btn-outline-danger js-confirm-delete" data-name="{{ $vendor->name }}">
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
                    {{ $vendors->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
