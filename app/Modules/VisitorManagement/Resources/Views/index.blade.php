@extends('layouts.app')

@section('title', 'Visitors')

@section('page-heading')
    <h1 class="h4 mb-0">Visitors</h1>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0 d-lg-none">Visitors</h1>
            <p class="text-secondary mb-0">{{ $visitors->total() }} {{ Str::plural('visit', $visitors->total()) }}</p>
        </div>
        @can('create', \App\Modules\VisitorManagement\Models\Visitor::class)
            <a href="{{ route('visitors.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Check In Visitor
            </a>
        @endcan
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-auto">
                    <div class="input-group" style="max-width: 320px;">
                        <span class="input-group-text bg-transparent"><i class="bi bi-search"></i></span>
                        <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search by name or plate…">
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
                        <option value="">All visits</option>
                        <option value="checked_in" @selected(request('status') === 'checked_in')>Checked In</option>
                        <option value="checked_out" @selected(request('status') === 'checked_out')>Checked Out</option>
                    </select>
                </div>
                <div class="col-auto">
                    <select name="purpose" class="form-select" onchange="this.form.submit()">
                        <option value="">All purposes</option>
                        @foreach (['delivery', 'meeting', 'maintenance', 'interview', 'personal', 'other'] as $purpose)
                            <option value="{{ $purpose }}" @selected(request('purpose') === $purpose)>{{ ucfirst($purpose) }}</option>
                        @endforeach
                    </select>
                </div>
            </form>

            @if ($visitors->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-person-badge display-4 text-secondary"></i>
                    <p class="mt-3 mb-1 fw-semibold">No visitors logged yet</p>
                    <p class="text-secondary">Visitors you check in will show up here.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Visitor</th>
                                <th>Property / Unit</th>
                                <th>Visiting</th>
                                <th>Check In</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($visitors as $item)
                                <tr>
                                    <td class="fw-semibold">{{ $item->visitor_name }}</td>
                                    <td>
                                        <div class="small">{{ $item->property->name }}</div>
                                        <div class="small text-secondary">{{ $item->unit->unit_number ?? '' }}</div>
                                    </td>
                                    <td class="small text-secondary">{{ $item->hostDisplayName() ?? '—' }}</td>
                                    <td class="small">{{ $item->check_in_at->format('M j, Y g:i A') }}</td>
                                    <td>
                                        @if ($item->isCheckedIn())
                                            <span class="badge text-bg-success">Checked In</span>
                                        @else
                                            <span class="badge text-bg-secondary">Checked Out</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('visitors.show', $item) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $visitors->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
