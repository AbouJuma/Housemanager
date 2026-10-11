@extends('landlord-portal::layout')

@section('title', __('Maintenance Requests'))

@section('content')
    <h1 class="h4 mb-4">{{ __('Maintenance Requests') }}</h1>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            @if ($requests->isEmpty())
                <p class="text-secondary small mb-0">No maintenance requests on your properties yet.</p>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>{{ __('Title') }}</th>
                                <th>{{ __('Property') }}</th>
                                <th>{{ __('Vendor') }}</th>
                                <th>{{ __('Priority') }}</th>
                                <th>{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($requests as $item)
                                <tr>
                                    <td class="fw-semibold">{{ $item->title }}</td>
                                    <td class="small text-secondary">{{ $item->property->name }}</td>
                                    <td class="small text-secondary">{{ $item->vendor->name ?? '—' }}</td>
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
