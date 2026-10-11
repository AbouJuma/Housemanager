@extends('layouts.app')

@section('title', __('Rent Roll'))

@section('page-heading')
    <h1 class="h4 mb-0">{{ __('Rent Roll') }}</h1>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0 d-lg-none">{{ __('Rent Roll') }}</h1>
            <p class="text-secondary mb-0">{{ $rows->count() }} {{ Str::plural('unit', $rows->count()) }}</p>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            @include('reports::partials.status-filter')
            <a href="{{ route('reports.rent-roll.export', ['format' => 'pdf', 'status' => $status]) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-file-earmark-pdf me-1"></i> PDF</a>
            <a href="{{ route('reports.rent-roll.export', ['format' => 'xlsx', 'status' => $status]) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-file-earmark-excel me-1"></i> Excel</a>
            <a href="{{ route('reports.rent-roll.export', ['format' => 'csv', 'status' => $status]) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-filetype-csv me-1"></i> CSV</a>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            @if ($rows->isEmpty())
                <p class="text-secondary small mb-0">No units on file.</p>
            @else
                <div class="table-responsive">
                    <table id="rentRollTable" class="table table-striped table-hover table-sm align-middle datatable">
                        <thead>
                            <tr>
                                <th>{{ __('Property') }}</th>
                                <th>{{ __('Building') }}</th>
                                <th>{{ __('Unit') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Tenant') }}</th>
                                <th>{{ __('Lease Start') }}</th>
                                <th>{{ __('Lease End') }}</th>
                                <th class="text-end">{{ __('Rent') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr>
                                    <td>{{ $row['property'] }}</td>
                                    <td>{{ $row['building'] }}</td>
                                    <td class="fw-semibold">{{ $row['unit'] }}</td>
                                    <td><span class="badge text-bg-{{ $row['status'] === 'vacant' ? 'warning' : 'success' }} text-capitalize">{{ $row['status'] }}</span></td>
                                    <td>{{ $row['tenant'] ?? '—' }}</td>
                                    <td class="small">{{ $row['lease_start'] ?? '—' }}</td>
                                    <td class="small">{{ $row['lease_end'] ?? '—' }}</td>
                                    <td class="text-end">{{ number_format($row['rent_amount'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
