@extends('layouts.app')

@section('title', __('Occupancy'))

@section('page-heading')
    <h1 class="h4 mb-0">{{ __('Occupancy') }}</h1>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0 d-lg-none">{{ __('Occupancy') }}</h1>
            <p class="text-secondary mb-0">{{ $rows->count() }} {{ Str::plural('property', $rows->count()) }}</p>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            @include('reports::partials.status-filter')
            <a href="{{ route('reports.occupancy.export', ['format' => 'pdf', 'status' => $status]) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-file-earmark-pdf me-1"></i> PDF</a>
            <a href="{{ route('reports.occupancy.export', ['format' => 'xlsx', 'status' => $status]) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-file-earmark-excel me-1"></i> Excel</a>
            <a href="{{ route('reports.occupancy.export', ['format' => 'csv', 'status' => $status]) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-filetype-csv me-1"></i> CSV</a>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            @if ($rows->isEmpty())
                <p class="text-secondary small mb-0">{{ __('No properties on file.') }}</p>
            @else
                <div class="table-responsive">
                    <table id="occupancyTable" class="table table-striped table-hover table-sm align-middle datatable">
                        <thead>
                            <tr>
                                <th>{{ __('Property') }}</th>
                                <th class="text-end">{{ __('Total Units') }}</th>
                                <th class="text-end">{{ __('Occupied') }}</th>
                                <th class="text-end">{{ __('Vacant') }}</th>
                                <th class="text-end">{{ __('Occupancy Rate') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr>
                                    <td class="fw-semibold">{{ $row['property'] }}</td>
                                    <td class="text-end">{{ $row['total_units'] }}</td>
                                    <td class="text-end">{{ $row['occupied'] }}</td>
                                    <td class="text-end">{{ $row['vacant'] }}</td>
                                    <td class="text-end">{{ $row['occupancy_rate'] }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
