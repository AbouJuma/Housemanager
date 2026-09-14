@extends('tenant-portal::layout')

@section('title', 'Maintenance Requests')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 mb-0">My Maintenance Requests</h1>
        <a href="{{ route('portal.maintenance-requests.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Report an Issue
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            @if ($requests->isEmpty())
                <p class="text-secondary small mb-0">No maintenance requests yet.</p>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Reported</th>
                                <th>Priority</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($requests as $item)
                                <tr>
                                    <td class="fw-semibold">{{ $item->title }}</td>
                                    <td class="text-capitalize">{{ str_replace('_', ' ', $item->category) }}</td>
                                    <td class="small">{{ $item->reported_date->toFormattedDateString() }}</td>
                                    <td><span class="badge text-bg-{{ match($item->priority) { 'urgent' => 'danger', 'high' => 'warning', 'low' => 'secondary', default => 'info' } }} text-capitalize">{{ $item->priority }}</span></td>
                                    <td><span class="badge text-bg-{{ match($item->status) { 'completed' => 'success', 'cancelled' => 'secondary', 'in_progress' => 'primary', default => 'warning' } }} text-capitalize">{{ str_replace('_', ' ', $item->status) }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
