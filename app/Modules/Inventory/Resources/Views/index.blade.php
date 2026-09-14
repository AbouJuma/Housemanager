@extends('layouts.app')

@section('title', 'Inventory')

@section('page-heading')
    <h1 class="h4 mb-0">Inventory</h1>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0 d-lg-none">Inventory</h1>
            <p class="text-secondary mb-0">{{ $items->total() }} {{ Str::plural('item', $items->total()) }}</p>
        </div>
        @can('create', \App\Modules\Inventory\Models\InventoryItem::class)
            <a href="{{ route('inventory-items.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> New Item
            </a>
        @endcan
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-auto">
                    <div class="input-group" style="max-width: 320px;">
                        <span class="input-group-text bg-transparent"><i class="bi bi-search"></i></span>
                        <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search by name or SKU…">
                    </div>
                </div>
                <div class="col-auto">
                    <select name="category" class="form-select" onchange="this.form.submit()">
                        <option value="">All categories</option>
                        @foreach (['appliance', 'furniture', 'tool', 'plumbing_supply', 'electrical_supply', 'cleaning_supply', 'hardware', 'safety_equipment', 'other'] as $category)
                            <option value="{{ $category }}" @selected(request('category') === $category)>{{ ucfirst(str_replace('_', ' ', $category)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">All statuses</option>
                        @foreach (['active', 'discontinued'] as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto d-flex align-items-center">
                    <div class="form-check">
                        <input type="checkbox" name="low_stock" value="1" id="lowStockOnly" class="form-check-input" onchange="this.form.submit()" @checked(request()->boolean('low_stock'))>
                        <label class="form-check-label" for="lowStockOnly">Low stock only</label>
                    </div>
                </div>
            </form>

            @if ($items->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-box-seam display-4 text-secondary"></i>
                    <p class="mt-3 mb-1 fw-semibold">No inventory items yet</p>
                    <p class="text-secondary">Items you add will show up here.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Property</th>
                                <th>On Hand</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $item)
                                <tr>
                                    <td class="fw-semibold">{{ $item->name }}</td>
                                    <td class="text-capitalize">{{ str_replace('_', ' ', $item->category) }}</td>
                                    <td class="small text-secondary">{{ $item->property->name ?? '—' }}</td>
                                    <td>
                                        {{ rtrim(rtrim(number_format($item->quantity_on_hand, 2), '0'), '.') }} {{ $item->unit_of_measure }}
                                        @if ($item->isLowStock())
                                            <span class="badge text-bg-warning ms-1">Low</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge text-bg-{{ $item->status === 'active' ? 'success' : 'secondary' }} text-capitalize">{{ $item->status }}</span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('inventory-items.show', $item) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $items->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
