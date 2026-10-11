@extends('tenant-portal::layout')

@section('title', __('My Leases'))

@section('content')
    <h1 class="h4 mb-4">{{ __('My Leases') }}</h1>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            @if ($leases->isEmpty())
                <p class="text-secondary small mb-0">No leases on file.</p>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
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
                                    <td>
                                        <div class="fw-semibold">{{ $lease->unit->unit_number }}</div>
                                        <div class="small text-secondary">{{ $lease->unit->building->property->name }}</div>
                                    </td>
                                    <td class="small">{{ $lease->start_date->format('M j, Y') }} &ndash; {{ $lease->end_date->format('M j, Y') }}</td>
                                    <td>{{ number_format($lease->rent_amount, 2) }}</td>
                                    <td><span class="badge text-bg-{{ $lease->status === 'active' ? 'success' : ($lease->status === 'expired' ? 'secondary' : 'danger') }} text-capitalize">{{ $lease->status }}</span></td>
                                    <td class="text-end">
                                        <a href="{{ route('portal.leases.show', $lease) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
