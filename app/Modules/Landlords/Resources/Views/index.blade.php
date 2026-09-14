@extends('layouts.app')

@section('title', 'Landlords')

@section('page-heading')
    <h1 class="h4 mb-0">Landlords</h1>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0 d-lg-none">Landlords</h1>
            <p class="text-secondary mb-0">{{ $landlords->total() }} {{ Str::plural('landlord', $landlords->total()) }}</p>
        </div>
        @can('create', \App\Modules\Landlords\Models\Landlord::class)
            <a href="{{ route('landlords.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> New Landlord
            </a>
        @endcan
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-auto">
                    <div class="input-group" style="max-width: 320px;">
                        <span class="input-group-text bg-transparent"><i class="bi bi-search"></i></span>
                        <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search landlords…">
                    </div>
                </div>
                <div class="col-auto">
                    <select name="type" class="form-select" onchange="this.form.submit()">
                        <option value="">All types</option>
                        @foreach (['individual', 'company'] as $type)
                            <option value="{{ $type }}" @selected(request('type') === $type)>{{ ucfirst($type) }}</option>
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

            @if ($landlords->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-person-badge display-4 text-secondary"></i>
                    <p class="mt-3 mb-1 fw-semibold">No landlords yet</p>
                    <p class="text-secondary">Landlords you add will show up here.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Landlord</th>
                                <th>Type</th>
                                <th>Contact</th>
                                <th>Properties</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($landlords as $landlord)
                                <tr>
                                    <td class="fw-semibold">{{ $landlord->name }}</td>
                                    <td class="text-capitalize">{{ $landlord->type }}</td>
                                    <td>
                                        <div class="small">{{ $landlord->email ?? '—' }}</div>
                                        <div class="small text-secondary">{{ $landlord->phone ?? '' }}</div>
                                    </td>
                                    <td>{{ $landlord->properties_count }}</td>
                                    <td>
                                        <span class="badge text-bg-{{ $landlord->status === 'active' ? 'success' : 'secondary' }} text-capitalize">{{ $landlord->status }}</span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('landlords.show', $landlord) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                                        @can('update', $landlord)
                                            <a href="{{ route('landlords.edit', $landlord) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                                        @endcan
                                        @can('delete', $landlord)
                                            <form action="{{ route('landlords.destroy', $landlord) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn btn-sm btn-outline-danger js-confirm-delete" data-name="{{ $landlord->name }}">
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
                    {{ $landlords->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
