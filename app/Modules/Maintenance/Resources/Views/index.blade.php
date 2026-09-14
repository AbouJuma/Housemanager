@extends('layouts.app')

@section('title', 'Maintenance')

@section('page-heading')
    <h1 class="h4 mb-0">Maintenance</h1>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0 d-lg-none">Maintenance</h1>
            <p class="text-secondary mb-0">{{ $requests->total() }} {{ Str::plural('request', $requests->total()) }}</p>
        </div>
        @can('create', \App\Modules\Maintenance\Models\MaintenanceRequest::class)
            <a href="{{ route('maintenance-requests.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> New Request
            </a>
        @endcan
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-auto">
                    <div class="input-group" style="max-width: 320px;">
                        <span class="input-group-text bg-transparent"><i class="bi bi-search"></i></span>
                        <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search by title or description…">
                    </div>
                </div>
                <div class="col-auto">
                    <select name="property_id" class="form-select" onchange="this.form.submit()">
                        <option value="">All properties</option>
                        @foreach ($properties as $property)
                            <option value="{{ $property->id }}" @selected((string) request('property_id') === (string) $property->id)>{{ $property->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">All statuses</option>
                        @foreach (['open', 'in_progress', 'completed', 'cancelled'] as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <select name="priority" class="form-select" onchange="this.form.submit()">
                        <option value="">All priorities</option>
                        @foreach (['low', 'medium', 'high', 'urgent'] as $priority)
                            <option value="{{ $priority }}" @selected(request('priority') === $priority)>{{ ucfirst($priority) }}</option>
                        @endforeach
                    </select>
                </div>
            </form>

            @if ($requests->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-tools display-4 text-secondary"></i>
                    <p class="mt-3 mb-1 fw-semibold">No maintenance requests yet</p>
                    <p class="text-secondary">Requests you log will show up here.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Property / Unit</th>
                                <th>Vendor</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($requests as $item)
                                <tr>
                                    <td class="fw-semibold">{{ $item->title }}</td>
                                    <td>
                                        <div class="small">{{ $item->property->name }}</div>
                                        <div class="small text-secondary">{{ $item->unit->unit_number ?? '' }}</div>
                                    </td>
                                    <td class="small text-secondary">{{ $item->vendor->name ?? '—' }}</td>
                                    <td>
                                        <span class="badge text-bg-{{ match($item->priority) { 'urgent' => 'danger', 'high' => 'warning', 'low' => 'secondary', default => 'info' } }} text-capitalize">{{ $item->priority }}</span>
                                    </td>
                                    <td>
                                        <span class="badge text-bg-{{ match($item->status) { 'completed' => 'success', 'cancelled' => 'secondary', 'in_progress' => 'primary', default => 'warning' } }} text-capitalize">{{ str_replace('_', ' ', $item->status) }}</span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('maintenance-requests.show', $item) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $requests->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
