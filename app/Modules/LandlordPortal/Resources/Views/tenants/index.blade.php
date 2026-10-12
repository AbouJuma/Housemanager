@extends('landlord-portal::layout')

@section('title', __('My Tenants'))

@section('content')
    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-4">
        <h1 class="h4 mb-0">{{ __('My Tenants') }}</h1>
        @if ($canCreate)
            <a href="{{ route('landlord-portal.tenants.create') }}" class="btn btn-primary">
                <i class="bi bi-person-plus me-1"></i> {{ __('Add Tenant') }}
            </a>
        @endif
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="GET" class="mb-3">
                <div class="input-group" style="max-width: 360px;">
                    <span class="input-group-text bg-transparent"><i class="bi bi-search"></i></span>
                    <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="{{ __('Search by name, phone or username…') }}">
                </div>
            </form>

            @if ($rows->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-people display-5 text-secondary"></i>
                    <p class="mt-3 mb-0 text-secondary">{{ __('No tenants yet.') }}</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Phone') }}</th>
                                <th>{{ __('Property / Unit') }}</th>
                                <th>{{ __('Rent') }}</th>
                                <th>{{ __('Lease') }}</th>
                                @if ($canUpdate)<th></th>@endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $row['name'] }}</div>
                                        <div class="text-secondary small">&#64;{{ $row['username'] }}</div>
                                    </td>
                                    <td>{{ $row['phone'] ?? '—' }}</td>
                                    <td>
                                        @if ($row['current_lease'])
                                            {{ $row['current_lease']['property']['name'] ?? '—' }}
                                            <div class="text-secondary small">{{ __('Unit') }} {{ $row['current_lease']['unit_number'] }}</div>
                                        @else
                                            <span class="text-secondary">{{ __('No lease yet') }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $row['current_lease'] ? number_format($row['current_lease']['rent_amount'], 2) : '—' }}</td>
                                    <td>
                                        @if ($row['current_lease'])
                                            <span class="badge text-bg-{{ $row['current_lease']['status'] === 'active' ? 'success' : 'secondary' }}">{{ __(ucfirst($row['current_lease']['status'])) }}</span>
                                            @if ($row['current_lease']['end_date'])
                                                <div class="text-secondary small">{{ __('Ends') }} {{ \Illuminate\Support\Carbon::parse($row['current_lease']['end_date'])->format('d M Y') }}</div>
                                            @endif
                                        @else
                                            —
                                        @endif
                                    </td>
                                    @if ($canUpdate)
                                        <td class="text-end"><a href="{{ route('landlord-portal.tenants.edit', $row['uuid']) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('Edit') }}"><i class="bi bi-pencil"></i></a></td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">{{ $tenants->links() }}</div>
            @endif
        </div>
    </div>
@endsection
