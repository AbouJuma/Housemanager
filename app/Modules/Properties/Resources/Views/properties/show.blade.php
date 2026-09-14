@extends('layouts.app')

@section('title', $property->name)

@section('page-heading')
    <h1 class="h4 mb-0">{{ $property->name }}</h1>
@endsection

@section('content')
    <x-breadcrumb :items="collect([
        ['label' => 'Properties', 'url' => route('properties.index')],
        ['label' => $property->name],
    ])" />

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0 d-lg-none">{{ $property->name }}</h1>
            <p class="text-secondary mb-0 text-capitalize">{{ str_replace('_', ' ', $property->type) }} &middot; {{ str_replace('_', ' ', $property->status) }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('properties.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> All Properties
            </a>
            @can('update', $property)
                <a href="{{ route('properties.edit', $property) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-pencil me-1"></i> Edit
                </a>
            @endcan
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h6 mb-3">Details</h2>
                    @if ($property->description)
                        <p>{{ $property->description }}</p>
                    @endif
                    <div class="row g-3 small">
                        <div class="col-md-6">
                            <div class="text-secondary">Address</div>
                            <div>
                                {{ collect([$property->address_line1, $property->address_line2, $property->city, $property->state, $property->postal_code, $property->country_code])->filter()->implode(', ') ?: '—' }}
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-secondary">Landlord</div>
                            <div>
                                @if ($property->landlord)
                                    <a href="{{ route('landlords.show', $property->landlord) }}">{{ $property->landlord->name }}</a>
                                @else
                                    &mdash;
                                @endif
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-secondary">Total Area</div>
                            <div>{{ $property->total_area ? number_format($property->total_area, 2).' '.strtoupper($property->area_unit) : '—' }}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-secondary">Coordinates</div>
                            <div>{{ $property->latitude && $property->longitude ? "{$property->latitude}, {$property->longitude}" : '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h6 mb-0">Buildings</h2>
                        @can('create', [\App\Modules\Properties\Models\Building::class, $property])
                            <a href="{{ route('buildings.create', $property) }}" class="btn btn-sm btn-primary">
                                <i class="bi bi-plus-lg me-1"></i> Add Building
                            </a>
                        @endcan
                    </div>

                    @if ($buildings->isEmpty())
                        <div class="text-center py-4">
                            <i class="bi bi-building display-6 text-secondary"></i>
                            <p class="mt-2 mb-0 text-secondary">No buildings yet.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Building</th>
                                        <th>Floors</th>
                                        <th>Units</th>
                                        <th>Status</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($buildings as $building)
                                        <tr>
                                            <td class="fw-semibold">{{ $building->name }}</td>
                                            <td>{{ $building->floor_count ?? '—' }}</td>
                                            <td>{{ $building->units_count }}</td>
                                            <td>
                                                <span class="badge text-bg-{{ $building->status === 'active' ? 'success' : 'secondary' }} text-capitalize">{{ str_replace('_', ' ', $building->status) }}</span>
                                            </td>
                                            <td class="text-end">
                                                <a href="{{ route('buildings.show', $building) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h6 mb-0">Maintenance</h2>
                        @can('create', \App\Modules\Maintenance\Models\MaintenanceRequest::class)
                            <a href="{{ route('maintenance-requests.create', ['property_id' => $property->uuid]) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-plus-lg me-1"></i> New Request
                            </a>
                        @endcan
                    </div>

                    @if ($maintenanceRequests->isEmpty())
                        <p class="text-secondary small mb-0">No maintenance requests yet.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Priority</th>
                                        <th>Status</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($maintenanceRequests as $item)
                                        <tr>
                                            <td class="fw-semibold">{{ $item->title }}</td>
                                            <td><span class="badge text-bg-{{ match($item->priority) { 'urgent' => 'danger', 'high' => 'warning', 'low' => 'secondary', default => 'info' } }} text-capitalize">{{ $item->priority }}</span></td>
                                            <td><span class="badge text-bg-{{ match($item->status) { 'completed' => 'success', 'cancelled' => 'secondary', 'in_progress' => 'primary', default => 'warning' } }} text-capitalize">{{ str_replace('_', ' ', $item->status) }}</span></td>
                                            <td class="text-end">
                                                <a href="{{ route('maintenance-requests.show', $item) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h6 mb-0">Inventory</h2>
                        @can('create', \App\Modules\Inventory\Models\InventoryItem::class)
                            <a href="{{ route('inventory-items.create', ['property_id' => $property->uuid]) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-plus-lg me-1"></i> New Item
                            </a>
                        @endcan
                    </div>

                    @if ($inventoryItems->isEmpty())
                        <p class="text-secondary small mb-0">No inventory items tied to this property yet.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>On Hand</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($inventoryItems as $item)
                                        <tr>
                                            <td class="fw-semibold">{{ $item->name }}</td>
                                            <td>
                                                {{ rtrim(rtrim(number_format($item->quantity_on_hand, 2), '0'), '.') }} {{ $item->unit_of_measure }}
                                                @if ($item->isLowStock())
                                                    <span class="badge text-bg-warning ms-1">Low</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <a href="{{ route('inventory-items.show', $item) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h6 mb-0">Documents</h2>
                        @can('create', \App\Modules\Documents\Models\Document::class)
                            <a href="{{ route('documents.create', ['documentable_type' => 'property', 'documentable_id' => $property->id]) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-plus-lg me-1"></i> Add Document
                            </a>
                        @endcan
                    </div>

                    @if ($documents->isEmpty())
                        <p class="text-secondary small mb-0">No documents attached to this property yet.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Category</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($documents as $item)
                                        <tr>
                                            <td class="fw-semibold">{{ $item->title }}</td>
                                            <td class="text-capitalize">{{ str_replace('_', ' ', $item->category) }}</td>
                                            <td class="text-end">
                                                <a href="{{ route('documents.show', $item) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h6 mb-0">Visitors</h2>
                        @can('create', \App\Modules\VisitorManagement\Models\Visitor::class)
                            <a href="{{ route('visitors.create', ['property_id' => $property->uuid]) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-plus-lg me-1"></i> Check In Visitor
                            </a>
                        @endcan
                    </div>

                    @if ($visitors->isEmpty())
                        <p class="text-secondary small mb-0">No visitors logged for this property yet.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Visitor</th>
                                        <th>Check In</th>
                                        <th>Status</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($visitors as $item)
                                        <tr>
                                            <td class="fw-semibold">{{ $item->visitor_name }}</td>
                                            <td class="small">{{ $item->check_in_at->format('M j, g:i A') }}</td>
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
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h6 mb-3">Photos</h2>
                    @if ($property->coverPhotoUrl())
                        <img src="{{ $property->coverPhotoUrl() }}" alt="{{ $property->name }}" class="img-fluid rounded-3">
                    @else
                        <p class="text-secondary small mb-0">No photos uploaded yet.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
